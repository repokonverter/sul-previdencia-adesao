<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Model\Table\IntegrationLogsTable;
use App\Services\IntegrationLogger;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * The bug this class is dangerous for is not "did it log" — it's "did it
 * leak a secret into the database" or "did it break the adhesion flow
 * because writing a log entry failed". These tests cover exactly those two
 * failure modes; everything else is exercised by using the app.
 */
class IntegrationLoggerTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        TableRegistry::getTableLocator()->clear();
    }

    public function testRedactsSensitiveKeysInRequestBody(): void
    {
        $largeBase64 = str_repeat('A', 2000);

        IntegrationLogger::logHttp([
            'service' => 'clicksign',
            'operation' => 'clicksign.create_document',
            'success' => true,
            'requestBody' => [
                'data' => [
                    'attributes' => [
                        'filename' => 'proposta.pdf',
                        'content_base64' => $largeBase64,
                    ],
                ],
            ],
        ]);

        $log = TableRegistry::getTableLocator()->get('IntegrationLogs')
            ->find()
            ->orderBy(['id' => 'DESC'])
            ->firstOrFail();

        $this->assertStringContainsString('[REDACTED,', $log->request_body);
        $this->assertStringNotContainsString($largeBase64, $log->request_body);
        $this->assertStringContainsString('proposta.pdf', $log->request_body);
    }

    public function testTruncatesLargeResponseBodies(): void
    {
        IntegrationLogger::logHttp([
            'service' => 'sicoob',
            'operation' => 'sicoob.get_cob',
            'success' => true,
            'responseBody' => ['field' => str_repeat('x', 20000)],
        ]);

        $log = TableRegistry::getTableLocator()->get('IntegrationLogs')
            ->find()
            ->orderBy(['id' => 'DESC'])
            ->firstOrFail();

        $this->assertStringEndsWith('…[truncado]', $log->response_body);
        $this->assertLessThan(9000, strlen($log->response_body));
    }

    public function testWriteFailureIsSwallowedAndNeverThrows(): void
    {
        $connection = TableRegistry::getTableLocator()->get('IntegrationLogs')->getConnection();

        $throwingTable = $this->getMockBuilder(IntegrationLogsTable::class)
            ->onlyMethods(['saveOrFail'])
            ->setConstructorArgs([['connection' => $connection]])
            ->getMock();
        $throwingTable->method('saveOrFail')->willThrowException(new RuntimeException('boom'));

        TableRegistry::getTableLocator()->set('IntegrationLogs', $throwingTable);

        IntegrationLogger::logHttp([
            'service' => 'resend',
            'operation' => 'resend.send',
            'success' => false,
        ]);

        $this->assertTrue(true);
    }
}
