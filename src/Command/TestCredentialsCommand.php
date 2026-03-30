<?php

declare(strict_types=1);

namespace App\Command;

use App\Services\ClicksignService;
use App\Services\SicoobService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

/**
 * Testa as credenciais do ClickSign e do Sicoob.
 *
 * Uso:
 *   bin/cake test_credentials
 *   bin/cake test_credentials --clicksign
 *   bin/cake test_credentials --sicoob
 */
class TestCredentialsCommand extends Command
{
    public static function defaultName(): string
    {
        return 'test_credentials';
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Testa as credenciais do ClickSign e do Sicoob.')
            ->addOption('clicksign', [
                'boolean' => true,
                'default' => false,
                'help' => 'Testa apenas as credenciais do ClickSign.',
            ])
            ->addOption('sicoob', [
                'boolean' => true,
                'default' => false,
                'help' => 'Testa apenas as credenciais do Sicoob.',
            ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $testClicksign = $args->getOption('clicksign');
        $testSicoob    = $args->getOption('sicoob');

        // Se nenhum flag for passado, testa os dois
        $testAll = !$testClicksign && !$testSicoob;

        $io->out('');
        $io->out('<info>========================================</info>');
        $io->out('<info>  Teste de Credenciais</info>');
        $io->out('<info>========================================</info>');
        $io->out('');

        $hasError = false;

        if ($testAll || $testClicksign) {
            if (!$this->testClicksign($io)) {
                $hasError = true;
            }
        }

        if ($testAll || $testSicoob) {
            if (!$this->testSicoob($io)) {
                $hasError = true;
            }
        }

        $io->out('');
        $io->out('<info>========================================</info>');

        if ($hasError) {
            $io->out('<error>  Resultado: FALHA em um ou mais serviços.</error>');
            $io->out('<info>========================================</info>');
            $io->out('');
            return self::CODE_ERROR;
        }

        $io->out('<success>  Resultado: Todas as credenciais OK!</success>');
        $io->out('<info>========================================</info>');
        $io->out('');
        return self::CODE_SUCCESS;
    }

    // -------------------------------------------------------------------------
    // ClickSign
    // -------------------------------------------------------------------------

    private function testClicksign(ConsoleIo $io): bool
    {
        $io->out('<info>--- ClickSign ---</info>');

        $baseUrl     = Configure::read('Clicksign.baseUrl');
        $accessToken = Configure::read('Clicksign.accessToken');

        $io->out("  Base URL    : {$baseUrl}");
        $io->out("  Access Token: " . $this->maskSecret((string)$accessToken));

        if (empty($baseUrl)) {
            $io->out('<error>  [ERRO] CLICKSIGN_BASE_URL não configurado.</error>');
            $io->out('');
            return false;
        }

        if (empty($accessToken)) {
            $io->out('<error>  [ERRO] CLICKSIGN_ACCESS_TOKEN não configurado.</error>');
            $io->out('');
            return false;
        }

        try {
            $io->out('  Testando conexão (GET /envelopes)...');
            $service  = new ClicksignService($baseUrl, $accessToken);
            $response = $service->getEnvelopes(['page[size]' => 1]);

            $io->out('<success>  [OK] ClickSign respondeu com sucesso.</success>');

            if (isset($response['meta']['record_count'])) {
                $io->out("  Total de envelopes: " . $response['meta']['record_count']);
            }
        } catch (\Exception $e) {
            $io->out('<error>  [ERRO] ' . $e->getMessage() . '</error>');
            $io->out('');
            return false;
        }

        $io->out('');
        return true;
    }

    // -------------------------------------------------------------------------
    // Sicoob
    // -------------------------------------------------------------------------

    private function testSicoob(ConsoleIo $io): bool
    {
        $io->out('<info>--- Sicoob ---</info>');

        $baseUrl          = Configure::read('Sicoob.baseUrl');
        $authUrl          = Configure::read('Sicoob.authUrl');
        $clientId         = Configure::read('Sicoob.clientId');
        $fixedToken       = Configure::read('Sicoob.fixedToken');
        $pixKey           = Configure::read('Sicoob.pixKey');
        $certBase64       = Configure::read('Sicoob.certificateBase64');
        $keyBase64        = Configure::read('Sicoob.privateKeyBase64');

        $io->out("  Base URL   : {$baseUrl}");
        $io->out("  Auth URL   : {$authUrl}");
        $io->out("  Client ID  : " . $this->maskSecret((string)$clientId));
        $io->out("  PIX Key    : {$pixKey}");
        $io->out("  Fixed Token: " . ($fixedToken ? $this->maskSecret((string)$fixedToken) : '(não definido)'));
        $io->out("  Cert Base64: " . ($certBase64 ? '<success>Definido (' . strlen(base64_decode((string)$certBase64)) . ' bytes)</success>' : '<error>NÃO definido</error>'));
        $io->out("  Key Base64 : " . ($keyBase64  ? '<success>Definido (' . strlen(base64_decode((string)$keyBase64))  . ' bytes)</success>' : '<error>NÃO definido</error>'));

        if (empty($baseUrl)) {
            $io->out('<error>  [ERRO] SICOOB_BASE_URL não configurado.</error>');
            $io->out('');
            return false;
        }

        if (empty($clientId) && empty($fixedToken)) {
            $io->out('<error>  [ERRO] Nenhuma credencial de auth (SICOOB_CLIENT_ID ou SICOOB_FIXED_TOKEN) configurada.</error>');
            $io->out('');
            return false;
        }

        try {
            $io->out('  Testando autenticação e conexão...');

            $config = [
                'baseUrl'           => $baseUrl,
                'authUrl'           => $authUrl,
                'clientId'          => $clientId,
                'certificateBase64' => $certBase64,
                'privateKeyBase64'  => $keyBase64,
                'fixedToken'        => $fixedToken,
                'pixKey'            => $pixKey,
            ];

            $service = new SicoobService($config);

            // Testa listando cobranças dos últimos 2 dias.
            // A data é obrigatória na API Sicoob PIX v2.
            $inicio = date('Y-m-d\TH:i:s\Z', strtotime('-2 days'));
            $fim    = date('Y-m-d\TH:i:s\Z');

            $response = $service->listCob([
                'inicio' => $inicio,
                'fim'    => $fim,
                'paginacao.itensPorPagina' => 1,
                'paginacao.paginaAtual'    => 0,
            ]);

            $io->out('<success>  [OK] Sicoob autenticou e respondeu com sucesso.</success>');

            if (isset($response['parametros']['paginacao']['quantidadeDePaginas'])) {
                $io->out("  Páginas de cobranças: " . $response['parametros']['paginacao']['quantidadeDePaginas']);
            }
        } catch (\Exception $e) {
            $io->out('<error>  [ERRO] ' . $e->getMessage() . '</error>');
            $io->out('');
            return false;
        }

        $io->out('');
        return true;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function maskSecret(string $value, int $visible = 6): string
    {
        if (strlen($value) <= $visible * 2) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, $visible) . str_repeat('*', 6) . substr($value, -$visible);
    }
}
