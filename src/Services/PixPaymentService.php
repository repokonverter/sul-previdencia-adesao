<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use App\Model\Table\PixTransactionsTable;
use Exception;
use InvalidArgumentException;

class PixPaymentService
{
    /**
     * @param string $provider Gravado em cada cobrança criada (pix_transactions.provider).
     */
    public function __construct(
        private PixGateway $gateway,
        private string $provider,
        private ?string $pixKey,
        private string $txidPrefix,
        private PixTransactionsTable $pixTransactions,
    ) {
    }

    /**
     * O serviço com o banco que recebe pelo plano (config/plans.php, `pix`).
     */
    public static function forPlan(BenefitPlan $plan, PixTransactionsTable $pixTransactions): self
    {
        return new self(
            self::gatewayFor($plan),
            $plan->pix['provider'],
            self::pixKeyFor($plan),
            $plan->txidPrefix,
            $pixTransactions
        );
    }

    public static function gatewayFor(BenefitPlan $plan): PixGateway
    {
        $provider = $plan->pix['provider'];

        return match ($provider) {
            'sicoob' => SicoobService::fromConfigure($plan->pix['config']),
            'bancodobrasil' => BancoDoBrasilService::fromConfigure($plan->pix['config']),
            default => throw new InvalidArgumentException("Banco Pix desconhecido no plano {$plan->slug}: \"$provider\"."),
        };
    }

    /**
     * A chave Pix da conta que recebe pelo plano.
     */
    public static function pixKeyFor(BenefitPlan $plan): ?string
    {
        return Configure::read($plan->pix['config'] . '.pixKey');
    }

    /**
     * Resolve o estado de pagamento de uma adesão consultando o banco.
     * Cria uma cobrança se nunca houve uma, ou se a última expirou/foi removida.
     *
     * @return array{status: 'paid'|'active'|'error', txid?: string, brcode?: string}
     */
    public function resolve(int $adhesionId, string $cpf, string $customerName, float $amount): array
    {
        $this->gateway->forAdhesion($adhesionId);

        $latest = $this->pixTransactions->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])
            ->orderBy(['attempt' => 'DESC'])
            ->first();

        // Cobrança criada em outro banco não se consulta neste: a adesão
        // ganha uma nova aqui, como se a anterior tivesse expirado.
        if ($latest && $latest->provider === $this->provider) {
            try {
                $cobResponse = $this->gateway->getCob($latest->txid);
                $status = $cobResponse['status'] ?? null;

                if ($status === 'CONCLUIDA') {
                    if (!$latest->paid) {
                        $latest = $this->pixTransactions->patchEntity($latest, [
                            'paid' => true,
                            'payment_date' => FrozenTime::now(),
                        ]);
                        $this->pixTransactions->save($latest);

                        IntegrationLogger::logEvent([
                            'adhesionId' => $adhesionId,
                            'operation' => 'pix.payment_confirmed',
                            'context' => ['txid' => $latest->txid],
                        ]);
                    }

                    return ['status' => 'paid', 'txid' => $latest->txid];
                }

                if ($status === 'ATIVA') {
                    return [
                        'status' => 'active',
                        'txid' => $latest->txid,
                        'brcode' => $cobResponse['brcode'] ?? $latest->brcode,
                    ];
                }
            } catch (Exception $e) {
                Log::warning('PixPaymentService: cobrança ' . $latest->txid . ' indisponível no ' . $this->provider . ': ' . $e->getMessage());
            }
        }

        return $this->createCharge($adhesionId, $cpf, $customerName, $amount, $latest ? $latest->attempt + 1 : 1);
    }

    /**
     * Confirma no banco se uma cobrança específica foi paga, e atualiza o registro local.
     * Usado pelo webhook (gatilho, nunca fonte de verdade) e pelo botão manual do admin.
     *
     * Cobrança de outro banco é tratada como não encontrada: o txid só tem
     * significado no banco que o emitiu.
     *
     * @return array{found: bool, paid?: bool, status?: string}
     */
    public function confirmIfPaid(string $txid): array
    {
        $pixTransaction = $this->pixTransactions->find()
            ->where(['txid' => $txid, 'provider' => $this->provider])
            ->first();

        if (!$pixTransaction)
            return ['found' => false];

        $this->gateway->forAdhesion($pixTransaction->adhesion_initial_data_id);

        $cobResponse = $this->gateway->getCob($txid);
        $status = $cobResponse['status'] ?? null;

        if ($status === 'CONCLUIDA' && !$pixTransaction->paid) {
            $pixTransaction = $this->pixTransactions->patchEntity($pixTransaction, [
                'paid' => true,
                'payment_date' => FrozenTime::now(),
            ]);
            $this->pixTransactions->save($pixTransaction);

            IntegrationLogger::logEvent([
                'adhesionId' => $pixTransaction->adhesion_initial_data_id,
                'operation' => 'pix.payment_confirmed',
                'context' => ['txid' => $txid],
            ]);
        }

        return ['found' => true, 'paid' => $status === 'CONCLUIDA', 'status' => $status];
    }

    private function createCharge(int $adhesionId, string $cpf, string $customerName, float $amount, int $attempt): array
    {
        $txid = self::generateTxid($this->txidPrefix, $adhesionId, $attempt);
        $cobData = [
            'calendario' => ['expiracao' => 86400],
            'devedor' => ['cpf' => $cpf, 'nome' => $customerName],
            'valor' => ['original' => number_format($amount, 2, '.', '')],
            'chave' => $this->pixKey,
            'solicitacaoPagador' => 'Pagamento Adesão',
        ];

        try {
            $cobResponse = $this->gateway->createCobWithTxid($txid, $cobData);
        } catch (Exception $e) {
            // txid já existe (retry concorrente ou reprocessamento): a cobrança já foi
            // criada por outra requisição, buscamos o resultado em vez de duplicar.
            $cobResponse = $this->gateway->getCob($txid);
        }

        $pixTransaction = $this->pixTransactions->newEmptyEntity();
        $pixTransaction = $this->pixTransactions->patchEntity($pixTransaction, [
            'adhesion_initial_data_id' => $adhesionId,
            'txid' => $txid,
            'attempt' => $attempt,
            'provider' => $this->provider,
            'amount' => $amount,
            'brcode' => $cobResponse['brcode'] ?? null,
        ]);

        if (!$this->pixTransactions->save($pixTransaction))
            throw new Exception('Falha ao salvar transação Pix: ' . json_encode($pixTransaction->getErrors()));

        IntegrationLogger::logEvent([
            'adhesionId' => $adhesionId,
            'operation' => $attempt > 1 ? 'pix.charge_regenerated' : 'pix.charge_created',
            'context' => ['txid' => $txid, 'attempt' => $attempt],
        ]);

        return ['status' => 'active', 'txid' => $txid, 'brcode' => $cobResponse['brcode'] ?? null];
    }

    /**
     * Determinístico: a mesma tentativa da mesma adesão gera o mesmo txid, e
     * a criação concorrente cai no "já existe" em vez de duplicar a cobrança.
     */
    public static function generateTxid(string $prefix, int $adhesionId, int $attempt): string
    {
        $base = $prefix . str_pad((string)$adhesionId, 6, '0', STR_PAD_LEFT) . str_pad((string)$attempt, 2, '0', STR_PAD_LEFT);
        $suffix = strtoupper(substr(md5($base), 0, 13));

        return $base . $suffix;
    }
}
