<?php

declare(strict_types=1);

namespace App\Services;

/**
 * A API Pix de um banco, no recorte que a adesão usa. Os bancos seguem o
 * padrão do Bacen (`PUT/GET /cob/{txid}`, `PUT/DELETE /webhook/{chave}`),
 * então as respostas têm o mesmo formato; o que muda de um para outro é
 * autenticação e URL.
 *
 * Cada plano de benefícios recebe num banco (config/plans.php, chave `pix`);
 * quem escolhe a implementação é PixPaymentService::forPlan().
 */
interface PixGateway
{
    /**
     * Associa as chamadas seguintes a uma adesão, para o log delas aparecer
     * na aba "Integrações" dela no admin.
     */
    public function forAdhesion(?int $adhesionId): static;

    /**
     * @throws \Exception
     */
    public function createCobWithTxid(string $txid, array $data): array;

    /**
     * @return array Inclui `status` (ATIVA, CONCLUIDA...) e `brcode`.
     * @throws \Exception
     */
    public function getCob(string $txid): array;

    /**
     * @throws \Exception
     */
    public function configureWebhook(string $chave, string $webhookUrl): array;

    /**
     * @throws \Exception
     */
    public function deleteWebhook(string $chave): array;
}
