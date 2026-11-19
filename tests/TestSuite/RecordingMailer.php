<?php

declare(strict_types=1);

namespace App\Test\TestSuite;

use App\Services\ResendService;

/**
 * Dublê do ResendService: guarda o que seria enviado em vez de enviar. Entra
 * no lugar do real com mockService(ResendService::class, ...).
 */
class RecordingMailer extends ResendService
{
    /**
     * @var list<array{to: array, subject: string, html: string}>
     */
    public array $sent = [];

    public bool $fail = false;

    public function __construct()
    {
        parent::__construct([]);
    }

    public function send(array $to, string $subject, string $html): bool
    {
        if ($this->fail) {
            return false;
        }

        $this->sent[] = compact('to', 'subject', 'html');

        return true;
    }

    /**
     * O token do último link de senha enviado.
     */
    public function lastToken(): ?string
    {
        for ($i = count($this->sent) - 1; $i >= 0; $i--) {
            if (preg_match('#/admin/users/reset-password/([a-f0-9]{64})#', $this->sent[$i]['html'], $m)) {
                return $m[1];
            }
        }

        return null;
    }
}
