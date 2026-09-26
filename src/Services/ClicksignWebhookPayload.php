<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Tenta achar o id da adesão no corpo de um webhook da Clicksign.
 *
 * Separado do controller para poder ser testado sem rede: resolver o id é
 * pura leitura de array, e o que de fato confirma algo -- ClicksignSignatureChecker
 * -- é sempre uma chamada GET à parte, nunca este parsing.
 *
 * O formato exato do payload dos eventos "close" e "document_closed" não é
 * documentado publicamente em detalhe -- só o rascunho de dois campos
 * (account, document), sem o corpo completo. Os caminhos abaixo são os mais
 * plausíveis dado o padrão JSON:API que o resto da API segue, mas não foram
 * confirmados contra uma entrega real (exigiria uma URL pública recebendo o
 * evento de verdade). Se nenhum bater, a resolução simplesmente falha, e o
 * botão "Atualizar status" no admin continua funcionando -- ele parte do
 * envelope_id já gravado, sem depender de adivinhar este JSON.
 */
final class ClicksignWebhookPayload
{
    private const PATHS = [
        ['document', 'attributes', 'metadata', 'adhesion_initial_data_id'],
        ['document', 'data', 'attributes', 'metadata', 'adhesion_initial_data_id'],
        ['event', 'data', 'document', 'attributes', 'metadata', 'adhesion_initial_data_id'],
    ];

    public static function resolveAdhesionId(array $body): ?int
    {
        foreach (self::PATHS as $path) {
            $value = $body;

            foreach ($path as $key) {
                $value = is_array($value) ? ($value[$key] ?? null) : null;
            }

            if ($value !== null && ctype_digit((string)$value)) {
                return (int)$value;
            }
        }

        return null;
    }

    public static function eventName(array $body): ?string
    {
        $name = $body['event']['name'] ?? null;

        return is_string($name) ? $name : null;
    }
}
