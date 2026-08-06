<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Throwable;

/**
 * Persists a timeline of every call made to (or received from) Sicoob,
 * Clicksign and Resend, plus internal milestones of the adhesion flow.
 *
 * Writing a log entry must never break the caller: any failure here is
 * swallowed and reported to the regular file/stdout log instead.
 */
class IntegrationLogger
{
    private const MAX_BODY_BYTES = 8192;

    private const SENSITIVE_KEYS = [
        'authorization',
        'client_id',
        'content_base64',
        'access_token',
        'apikey',
        'api_key',
        'password',
        'private_key',
        'privatekey',
        'certificate',
        'secret',
    ];

    /**
     * Logs an HTTP call to/from a third-party integration.
     *
     * @param array{
     *     adhesionId?: int|null,
     *     service: string,
     *     operation: string,
     *     direction?: string,
     *     httpMethod?: string|null,
     *     url?: string|null,
     *     statusCode?: int|null,
     *     success?: bool,
     *     durationMs?: int|null,
     *     requestBody?: mixed,
     *     responseBody?: mixed,
     *     errorMessage?: string|null,
     *     context?: mixed,
     * } $attrs
     */
    public static function logHttp(array $attrs): void
    {
        try {
            $table = TableRegistry::getTableLocator()->get('IntegrationLogs');

            $entity = $table->newEntity([
                'adhesion_initial_data_id' => $attrs['adhesionId'] ?? null,
                'service' => $attrs['service'],
                'operation' => $attrs['operation'],
                'direction' => $attrs['direction'] ?? 'outbound',
                'http_method' => $attrs['httpMethod'] ?? null,
                'url' => $attrs['url'] ?? null,
                'status_code' => $attrs['statusCode'] ?? null,
                'success' => (bool)($attrs['success'] ?? false),
                'duration_ms' => $attrs['durationMs'] ?? null,
                'request_body' => self::serializeBody($attrs['requestBody'] ?? null),
                'response_body' => self::serializeBody($attrs['responseBody'] ?? null),
                'error_message' => $attrs['errorMessage'] ?? null,
                'context' => self::serializeBody($attrs['context'] ?? null),
            ], ['validate' => false]);

            $table->saveOrFail($entity);
        } catch (Throwable $e) {
            Log::error(
                'IntegrationLogger: falha ao gravar log de integração ('
                    . ($attrs['service'] ?? '?') . '.' . ($attrs['operation'] ?? '?') . '): '
                    . $e->getMessage()
            );
        }
    }

    /**
     * Logs an internal milestone (no HTTP call of our own involved),
     * e.g. "adhesion.finalized" or "pix.charge_created".
     *
     * @param array{
     *     adhesionId?: int|null,
     *     service?: string,
     *     operation: string,
     *     success?: bool,
     *     errorMessage?: string|null,
     *     context?: mixed,
     * } $attrs
     */
    public static function logEvent(array $attrs): void
    {
        self::logHttp([
            'adhesionId' => $attrs['adhesionId'] ?? null,
            'service' => $attrs['service'] ?? 'internal',
            'operation' => $attrs['operation'],
            'direction' => 'internal',
            'success' => $attrs['success'] ?? true,
            'errorMessage' => $attrs['errorMessage'] ?? null,
            'context' => $attrs['context'] ?? null,
        ]);
    }

    /**
     * Derives an operation label from the name of the calling method, e.g.
     * a `createEnvelope()` method calling this one frame up yields
     * "create_envelope". Lets every request-dispatching method in a service
     * (ClicksignService::_request, SicoobService::request) get a distinct,
     * readable operation label without having to pass one explicitly at
     * each of its ~15-20 call sites.
     */
    public static function operationFromCaller(int $depth = 2): string
    {
        // Frame 0 is this method itself, frame 1 is whoever called it (e.g.
        // ClicksignService::_request), so the caller we actually want — the
        // public method a caller invoked (e.g. createEnvelope) — is frame 2.
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 1);
        $function = $trace[$depth]['function'] ?? 'unknown';

        return strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', $function));
    }

    public static function elapsedMs(float $startedAt): int
    {
        return (int)round((microtime(true) - $startedAt) * 1000);
    }

    private static function serializeBody(mixed $body): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }

        if (is_string($body)) {
            $decoded = json_decode($body, true);
            $body = json_last_error() === JSON_ERROR_NONE ? $decoded : $body;
        }

        if (is_array($body)) {
            $encoded = json_encode(self::redact($body), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $encoded = $encoded === false ? '[não foi possível serializar]' : $encoded;
        } else {
            $encoded = (string)$body;
        }

        return self::truncate($encoded);
    }

    private static function redact(array $value): array
    {
        $redacted = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $redacted[$key] = self::redactedPlaceholder($item);
                continue;
            }

            $redacted[$key] = is_array($item) ? self::redact($item) : $item;
        }

        return $redacted;
    }

    private static function redactedPlaceholder(mixed $value): string
    {
        if (is_string($value) && strlen($value) > 256) {
            return '[REDACTED, ' . self::humanBytes(strlen($value)) . ']';
        }

        return '[REDACTED]';
    }

    private static function humanBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    private static function truncate(string $value): string
    {
        if (strlen($value) <= self::MAX_BODY_BYTES) {
            return $value;
        }

        return substr($value, 0, self::MAX_BODY_BYTES) . '…[truncado]';
    }
}
