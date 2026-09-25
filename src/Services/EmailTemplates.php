<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Routing\Router;

class EmailTemplates
{
    /**
     * O prazo vai escrito no corpo de propósito: sem ele, o suporte recebe
     * "cliquei e não funcionou" de quem guardou o e-mail por duas semanas.
     */
    public static function resumeProposal(string $customerName, string $resumeUrl, string $stepLabel, int $days): string
    {
        $logoUrl = Router::url('/img/logo_sul_transparente.png', true);

        return self::wrap($logoUrl, '
            <h1 style="margin:0 0 16px;font-size:22px;color:#333333;">Sua proposta está guardada, ' . h($customerName) . '</h1>
            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#555555;">
                Tudo o que você já preencheu continua salvo. Para continuar de onde parou,
                em <strong>' . h($stepLabel) . '</strong>, é só clicar no botão abaixo.
            </p>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                <tr>
                    <td style="border-radius:8px;background-color:#FC7A29;">
                        <a href="' . h($resumeUrl) . '" style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:8px;">
                            Continuar minha proposta
                        </a>
                    </td>
                </tr>
            </table>
            <p style="margin:0 0 8px;font-size:13px;color:#888888;">
                Ou copie e cole este link no navegador:<br>
                <a href="' . h($resumeUrl) . '" style="color:#FC7A29;word-break:break-all;">' . h($resumeUrl) . '</a>
            </p>
            <p style="margin:24px 0 0;font-size:13px;color:#888888;">
                Este link vale por ' . $days . ' dias. Depois disso, fale com seu atendente
                que ele envia um novo — seus dados continuam guardados de qualquer forma.
            </p>
        ');
    }

    public static function paymentLink(string $customerName, string $paymentUrl): string
    {
        $logoUrl = Router::url('/img/logo_sul_transparente.png', true);

        return self::wrap($logoUrl, '
            <h1 style="margin:0 0 16px;font-size:22px;color:#333333;">Falta pouco, ' . h($customerName) . '!</h1>
            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#555555;">
                Recebemos sua adesão ao plano de previdência Sul Previdência. Para concluir,
                falta apenas o pagamento via Pix. Clique no botão abaixo para ver o QR Code
                e o código copia e cola.
            </p>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                <tr>
                    <td style="border-radius:8px;background-color:#FC7A29;">
                        <a href="' . h($paymentUrl) . '" style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:8px;">
                            Pagar com Pix
                        </a>
                    </td>
                </tr>
            </table>
            <p style="margin:0 0 8px;font-size:13px;color:#888888;">
                Ou copie e cole este link no navegador:<br>
                <a href="' . h($paymentUrl) . '" style="color:#FC7A29;word-break:break-all;">' . h($paymentUrl) . '</a>
            </p>
            <p style="margin:24px 0 0;font-size:13px;color:#888888;">
                A cobrança Pix é válida por 24 horas a partir do momento em que a página é aberta.
                Se expirar, basta reabrir o link que geramos uma nova automaticamente.
            </p>
        ');
    }

    public static function clicksignFailureAlert(int $initialDataId, string $customerName, string $errorMessage): string
    {
        return self::wrap(null, '
            <h1 style="margin:0 0 16px;font-size:20px;color:#333333;">Falha na assinatura eletrônica</h1>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#555555;">
                A integração com o Clicksign falhou para a adesão <strong>#' . $initialDataId . '</strong> (' . h($customerName) . ').
            </p>
            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#c0392b;background:#fdf1f0;padding:12px;border-radius:6px;">
                ' . h($errorMessage) . '
            </p>
            <p style="margin:0;font-size:14px;line-height:1.6;color:#555555;">
                A adesão foi salva normalmente; a assinatura precisa ser reprocessada manualmente.
            </p>
        ');
    }

    private static function wrap(?string $logoUrl, string $bodyHtml): string
    {
        $logoHtml = $logoUrl
            ? '<img src="' . h($logoUrl) . '" alt="Sul Previdência" style="height:48px;margin-bottom:24px;">'
            : '<div style="font-size:16px;font-weight:bold;color:#333333;margin-bottom:24px;">Sul Previdência</div>';

        return '
        <html>
        <body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Arial,Helvetica,sans-serif;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:32px 16px;">
                <tr>
                    <td align="center">
                        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                            <tr>
                                <td style="height:8px;background-color:#FC7A29;"></td>
                            </tr>
                            <tr>
                                <td style="padding:32px;text-align:center;">
                                    ' . $logoHtml . $bodyHtml . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 32px;background-color:#fafafa;text-align:center;">
                                    <p style="margin:0;font-size:12px;color:#aaaaaa;">Sul Previdência &middot; Este é um e-mail automático, não é necessário responder.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
}
