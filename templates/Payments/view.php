<?php

/**
 * @var \App\View\AppView $this
 * @var string $customerName
 * @var float $amount
 * @var array $result
 * @var string|null $qrCodeBase64
 */

use Cake\I18n\Number;

$this->assign('title', 'Sul Previdência - Pagamento da Adesão');

$logoAssetPath = 'logo_sul_transparente.png';
?>
<style>
    :root {
        --primary-color: rgb(252, 122, 41);
        --white: #FFFFFF;
        --text-color: #333333;
        --card-shadow: 0px 4px 24px rgba(0, 0, 0, 0.10);
    }

    body {
        background: #fff !important;
        min-height: 100vh;
        margin: 0;
        font-family: Arial, sans-serif;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .payment-popup {
        max-width: 560px;
        width: 100%;
        margin: 48px auto;
        background: #fff;
        border-radius: 24px;
        box-shadow: var(--card-shadow);
        padding: 0 0 32px 0;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .payment-header-bar {
        width: 100%;
        height: 10px;
        background: var(--primary-color);
        border-radius: 24px 24px 0 0;
    }

    .payment-content {
        width: 100%;
        padding: 32px;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-sizing: border-box;
    }

    .payment-logo {
        height: 80px;
        margin-bottom: 24px;
    }

    .payment-title {
        text-align: center;
        font-size: 1.6rem;
        font-weight: bold;
        color: var(--text-color);
        margin-bottom: 8px;
    }

    .payment-subtitle {
        text-align: center;
        font-size: 1rem;
        color: #555;
        margin-bottom: 24px;
    }

    .payment-amount {
        font-size: 1.4rem;
        font-weight: bold;
        color: var(--primary-color);
        margin-bottom: 24px;
    }

    .payment-qrcode {
        width: 220px;
        height: 220px;
        border: 1px solid #eee;
        border-radius: 8px;
        margin-bottom: 16px;
    }

    .payment-status-icon {
        font-size: 3rem;
        margin-bottom: 16px;
    }
</style>

<div class="payment-popup">
    <div class="payment-header-bar"></div>
    <div class="payment-content">
        <?= $this->Html->image($logoAssetPath, ['alt' => 'Sul Previdencia', 'class' => 'payment-logo']) ?>

        <?php if ($result['status'] === 'paid'): ?>
            <div class="payment-status-icon">✅</div>
            <div class="payment-title">Pagamento confirmado</div>
            <p class="payment-subtitle">Olá, <?= h($customerName) ?>. Recebemos a confirmação do pagamento da sua adesão. Obrigado!</p>
        <?php elseif ($result['status'] === 'active' && $qrCodeBase64): ?>
            <div class="payment-title">Pagamento da adesão</div>
            <p class="payment-subtitle">Olá, <?= h($customerName) ?>. Utilize o QR Code ou o Pix Copia e Cola abaixo para concluir sua adesão.</p>
            <div class="payment-amount"><?= Number::currency($amount) ?></div>
            <img src="<?= h($qrCodeBase64) ?>" alt="QR Code PIX" class="payment-qrcode">
            <div class="mb-3 w-100">
                <label for="pix-copy-paste" class="form-label">Pix Copia e Cola</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="pix-copy-paste" value="<?= h($result['brcode']) ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyPixCode()">Copiar</button>
                </div>
            </div>
            <p class="payment-subtitle" style="margin-top: 16px; font-size: 0.85rem;">Esta cobrança é válida por 24 horas. Se expirar, basta recarregar esta página que geraremos uma nova.</p>
        <?php else: ?>
            <div class="payment-status-icon">⏳</div>
            <div class="payment-title">Não foi possível carregar a cobrança</div>
            <p class="payment-subtitle">Sua adesão foi registrada com sucesso, mas não conseguimos gerar a cobrança Pix agora. Tente recarregar esta página em alguns instantes ou aguarde que nossa equipe entrará em contato.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    function copyPixCode() {
        const input = document.getElementById('pix-copy-paste');
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value);
    }
</script>
