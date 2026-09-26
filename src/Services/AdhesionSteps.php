<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Datasource\EntityInterface;

/**
 * As etapas do formulário, do lado do servidor.
 *
 * O formulário conhece a ordem em JavaScript; o admin precisa conhecê-la aqui
 * para oferecer "em que etapa o proponente recomeça". As duas listas precisam
 * concordar — a ordem abaixo é a mesma de `registerPages`, em
 * templates/Simulator/index.php, e o id é o mesmo em ambas justamente para
 * que o link guarde `'plan'` e não uma posição, que mudaria de significado no
 * próximo reordenamento.
 */
final class AdhesionSteps
{
    /**
     * Ordem canônica, espelhando registerPages. "conclusion" fica de fora:
     * não é etapa a preencher, é a tela de fim.
     */
    public const ORDER = [
        'initialData' => 'Dados iniciais',
        'personalData' => 'Dados pessoais',
        'documents' => 'Documentos',
        'dependents' => 'Beneficiário(s)',
        'addressData' => 'Endereço',
        'otherInformation' => 'Outras informações',
        'pensionScheme' => 'Regime de previdência',
        'plan' => 'Plano',
        'proponentStatement' => 'Declarações do proponente',
        'paymentDetail' => 'Dados para pagamento',
    ];

    /**
     * A associação cuja existência prova que a etapa foi preenchida.
     *
     * Beneficiários e regime de previdência não estão aqui de propósito: nos
     * dois, nenhuma linha é resposta legítima — não ter beneficiário, não
     * estar em regime nenhum — e o banco não distingue "respondeu que não" de
     * "não respondeu". Tratá-los como sempre completos erra para o lado de
     * deixar o admin escolher, que é recuperável; o contrário travaria o
     * seletor sem motivo.
     */
    private const EVIDENCE = [
        'personalData' => 'adhesion_personal_data',
        'documents' => 'adhesion_document',
        'addressData' => 'adhesion_address',
        'otherInformation' => 'adhesion_other_information',
        'plan' => 'adhesion_plan',
        'proponentStatement' => 'adhesion_proponent_statement',
        'paymentDetail' => 'adhesion_payment_detail',
    ];

    public static function isComplete(EntityInterface $adhesion, string $step): bool
    {
        if ($step === 'initialData') {
            return true; // a adesão existir já é a prova
        }

        $property = self::EVIDENCE[$step] ?? null;

        return $property === null || $adhesion->get($property) !== null;
    }

    /**
     * A declaração de saúde só é exigível quando há risco contratado — sem
     * nenhum, a etapa nem aparece para o proponente, e cobrá-la aqui
     * bloquearia o seletor por uma etapa que ele nunca veria.
     */
    private static function isRequired(EntityInterface $adhesion, string $step): bool
    {
        if ($step !== 'proponentStatement') {
            return true;
        }

        $plan = $adhesion->get('adhesion_plan');

        return ($plan?->has_survivors_pension ?? true) || ($plan?->has_disability_retirement ?? true);
    }

    /**
     * As etapas, cada uma dizendo se pode ser escolhida e, quando não pode,
     * por quê.
     *
     * Escolher uma etapa cujas anteriores estão em branco deixaria a lacuna
     * passar: nextPage() valida só a etapa corrente, e a adesão seria
     * finalizada sem endereço, gerando PDF e envelope incompletos.
     *
     * @return array<string, array{label: string, selectable: bool, complete: bool, reason: string|null}>
     */
    public static function forPicker(EntityInterface $adhesion): array
    {
        $steps = [];
        $missing = null;

        foreach (self::ORDER as $step => $label) {
            $complete = self::isComplete($adhesion, $step);

            $steps[$step] = [
                'label' => $label,
                'complete' => $complete,
                'selectable' => $missing === null,
                'reason' => $missing === null ? null : 'Depende de "' . self::ORDER[$missing] . '", ainda em branco.',
            ];

            if (!$complete && $missing === null && self::isRequired($adhesion, $step)) {
                $missing = $step;
            }
        }

        return $steps;
    }

    /**
     * A etapa que o seletor traz pré-escolhida: a primeira que falta, que é o
     * caso de uso normal — "continue de onde parou".
     */
    public static function firstIncomplete(EntityInterface $adhesion): string
    {
        foreach (array_keys(self::ORDER) as $step) {
            if (!self::isComplete($adhesion, $step) && self::isRequired($adhesion, $step)) {
                return $step;
            }
        }

        return array_key_last(self::ORDER);
    }

    public static function exists(?string $step): bool
    {
        return $step !== null && array_key_exists($step, self::ORDER);
    }
}
