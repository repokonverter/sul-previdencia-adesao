<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Entity\AdhesionAudit;
use App\Model\Table\AdhesionAuditsTable;
use Cake\Datasource\EntityInterface;
use Cake\Log\Log;
use DateTimeInterface;

/**
 * Registra o que mudou numa adesão editada pelo admin, e por quem.
 *
 * O diff é tirado comparando dois retratos da adesão inteira — antes e depois
 * da gravação — em vez de confiar no rastreio de "campo sujo" do ORM. Dois
 * motivos: o patch marca como sujo campo reescrito com o mesmo valor
 * (produzindo mudança que não houve), e os beneficiários são uma associação
 * hasMany, cujo acréscimo e remoção o rastreio de campo não enxerga.
 */
class AdhesionAuditor
{
    /**
     * O que a adesão precisa ter carregado para o retrato ficar completo.
     */
    public const CONTAINS = [
        'AdhesionPersonalDatas',
        'AdhesionDocuments',
        'AdhesionPlans',
        'AdhesionDependents',
        'AdhesionAddresses',
        'AdhesionOtherInformations',
        'AdhesionProponentStatements',
        'AdhesionPensionSchemes',
        'AdhesionPaymentDetails',
    ];

    /**
     * Chaves que não são conteúdo: identificadores, carimbos de tempo e as
     * próprias colunas de auditoria (senão toda edição registraria que
     * registrou uma edição).
     */
    private const IGNORED = [
        'id',
        'created',
        'updated',
        'modified',
        'adhesion_initial_data_id',
        'admin_overridden_at',
        'admin_overridden_by_user_id',
    ];

    public function __construct(private readonly AdhesionAuditsTable $audits)
    {
    }

    /**
     * Retrato plano da adesão: caminho pontuado => valor comparável.
     *
     * @return array<string, scalar|null>
     */
    public static function snapshot(EntityInterface $adhesion): array
    {
        return self::flatten($adhesion->toArray());
    }

    /**
     * @param array<string, scalar|null> $before
     * @param array<string, scalar|null> $after
     * @return array<string, array{0: scalar|null, 1: scalar|null}>
     */
    public static function diff(array $before, array $after): array
    {
        $changes = [];

        foreach (array_keys($before + $after) as $field) {
            $was = $before[$field] ?? null;
            $now = $after[$field] ?? null;

            // Comparação frouxa de propósito: '160.00' e '160.0' saem do banco
            // e do formulário com grafias diferentes para o mesmo dinheiro, e
            // registrar isso como alteração encheria a trilha de ruído.
            if (self::comparable($was) === self::comparable($now)) {
                continue;
            }

            $changes[$field] = [$was, $now];
        }

        ksort($changes);

        return $changes;
    }

    /**
     * @param array<string, array{0: scalar|null, 1: scalar|null}> $changes
     */
    public function record(int $adhesionId, ?EntityInterface $user, string $action, array $changes = []): ?AdhesionAudit
    {
        // Uma edição que não mudou nada não vira linha: a trilha existe para
        // ser lida, e "editou sem alterar" é ruído puro. Criação e exclusão
        // entram mesmo sem diff, porque o fato em si é a informação.
        if ($changes === [] && $action === AdhesionAudit::ACTION_UPDATED) {
            return null;
        }

        $audit = $this->audits->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'user_id' => $user?->get('id'),
            'user_name' => $user?->get('name'),
            'action' => $action,
            'changes' => $changes === [] ? null : json_encode($changes, JSON_UNESCAPED_UNICODE),
        ]);

        if ($this->audits->save($audit)) {
            return $audit;
        }

        // Falhar a auditoria nunca pode desfazer a edição que já foi gravada:
        // perder o registro é ruim, perder a alteração do cliente é pior.
        Log::error('Falha ao gravar auditoria da adesão #' . $adhesionId . ': ' . json_encode($audit->getErrors()));

        return null;
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, scalar|null>
     */
    private static function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            if (in_array($key, self::IGNORED, true)) {
                continue;
            }

            $path = $prefix === '' ? (string)$key : $prefix . '.' . $key;

            if (is_array($value)) {
                $flat += self::flatten($value, $path);

                // Acréscimo e remoção numa lista (beneficiários, regimes)
                // apareceriam só como campos que surgem ou somem; a contagem
                // torna "passou de 2 para 3 beneficiários" explícito.
                if (array_is_list($value)) {
                    $flat[$path . '.total'] = count($value);
                }

                continue;
            }

            $flat[$path] = self::normalize($value);
        }

        return $flat;
    }

    private static function normalize(mixed $value): string|int|float|bool|null
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string)$value : null;
        }

        return $value;
    }

    private static function comparable(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // Dinheiro e percentual chegam ora como '160.00', ora como 160.0:
        // normalizar o numérico impede diferença de grafia virar alteração.
        if (is_numeric($value)) {
            return rtrim(rtrim(number_format((float)$value, 6, '.', ''), '0'), '.');
        }

        return (string)$value;
    }
}
