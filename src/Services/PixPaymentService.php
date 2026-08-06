<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use App\Model\Table\PixTransactionsTable;
use Exception;

class PixPaymentService
{
    public function __construct(
        private SicoobService $sicoob,
        private PixTransactionsTable $pixTransactions,
    ) {
    }

    /**
     * Resolve o estado de pagamento de uma adesão consultando o Sicoob.
     * Cria uma cobrança se nunca houve uma, ou se a última expirou/foi removida.
     *
     * @return array{status: 'paid'|'active'|'error', txid?: string, brcode?: string}
     */
    public function resolve(int $adhesionId, string $cpf, string $customerName, float $amount): array
    {
        $this->sicoob->forAdhesion($adhesionId);

        $latest = $this->pixTransactions->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])
            ->orderBy(['attempt' => 'DESC'])
            ->first();

        if ($latest) {
            try {
                $cobResponse = $this->sicoob->getCob($latest->txid);
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
                Log::warning('PixPaymentService: cobrança ' . $latest->txid . ' indisponível no Sicoob: ' . $e->getMessage());
            }
        }

        return $this->createCharge($adhesionId, $cpf, $customerName, $amount, $latest ? $latest->attempt + 1 : 1);
    }

    /**
     * Confirma via Sicoob se uma cobrança específica foi paga, e atualiza o registro local.
     * Usado pelo webhook (gatilho, nunca fonte de verdade) e pelo botão manual do admin.
     *
     * @return array{found: bool, paid?: bool, status?: string}
     */
    public function confirmIfPaid(string $txid): array
    {
        $pixTransaction = $this->pixTransactions->find()->where(['txid' => $txid])->first();

        if (!$pixTransaction)
            return ['found' => false];

        $this->sicoob->forAdhesion($pixTransaction->adhesion_initial_data_id);

        $cobResponse = $this->sicoob->getCob($txid);
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
        $txid = self::generateTxid($adhesionId, $attempt);
        $cobData = [
            'calendario' => ['expiracao' => 86400],
            'devedor' => ['cpf' => $cpf, 'nome' => $customerName],
            'valor' => ['original' => number_format($amount, 2, '.', '')],
            'chave' => Configure::read('Sicoob.pixKey'),
            'solicitacaoPagador' => 'Pagamento Adesão',
        ];

        try {
            $cobResponse = $this->sicoob->createCobWithTxid($txid, $cobData);
        } catch (Exception $e) {
            // txid já existe (retry concorrente ou reprocessamento): a cobrança já foi
            // criada por outra requisição, buscamos o resultado em vez de duplicar.
            $cobResponse = $this->sicoob->getCob($txid);
        }

        $pixTransaction = $this->pixTransactions->newEmptyEntity();
        $pixTransaction = $this->pixTransactions->patchEntity($pixTransaction, [
            'adhesion_initial_data_id' => $adhesionId,
            'txid' => $txid,
            'attempt' => $attempt,
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

    public static function generateTxid(int $adhesionId, int $attempt): string
    {
        $base = 'PLENOPREV' . str_pad((string)$adhesionId, 6, '0', STR_PAD_LEFT) . str_pad((string)$attempt, 2, '0', STR_PAD_LEFT);
        $suffix = strtoupper(substr(md5($base), 0, 13));

        return $base . $suffix;
    }
}
