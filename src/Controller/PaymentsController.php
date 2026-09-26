<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use App\Services\IntegrationLogger;
use App\Services\PixPaymentService;
use App\Services\SicoobService;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class PaymentsController extends AppController
{
    public function view(string $storageUuid)
    {
        $adhesion = $this->fetchTable('AdhesionInitialDatas')->find()
            ->where(['AdhesionInitialDatas.storage_uuid' => $storageUuid])
            ->contain(['AdhesionPersonalDatas', 'AdhesionPaymentDetails', 'PromotionalCodes.Partners'])
            ->first();

        if (!$adhesion || !$adhesion->adhesion_payment_detail)
            throw new NotFoundException('Pagamento não encontrado.');

        IntegrationLogger::logEvent([
            'adhesionId' => $adhesion->id,
            'operation' => 'payment_page.opened',
        ]);

        $customerName = $adhesion->adhesion_personal_data->name ?? 'Cliente';
        $partner = $adhesion->promotional_code_entity->partner ?? null;
        $cpf = preg_replace('/\D/', '', $adhesion->adhesion_personal_data->cpf ?? '');
        $amount = (float)$adhesion->adhesion_payment_detail->total_contribution;

        try {
            $pixPaymentService = new PixPaymentService(SicoobService::fromConfigure(), $this->fetchTable('PixTransactions'));
            $result = $pixPaymentService->resolve($adhesion->id, $cpf, $customerName, $amount);
        } catch (\Exception $e) {
            Log::error('Erro ao resolver cobrança Pix da adesão #' . $adhesion->id . ': ' . $e->getMessage());
            $result = ['status' => 'error'];
        }

        $qrCodeBase64 = null;

        if ($result['status'] === 'active' && !empty($result['brcode'])) {
            $qrCodeBase64 = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($result['brcode'])
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(300)
                ->margin(10)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->validateResult(false)
                ->build()
                ->getDataUri();
        }

        $this->set(compact('customerName', 'amount', 'result', 'qrCodeBase64', 'partner'));
    }
}
