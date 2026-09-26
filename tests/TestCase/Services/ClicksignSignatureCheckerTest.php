<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Model\Entity\ClicksignData;
use App\Services\ClicksignSignatureChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O que efetivamente marca uma tentativa como assinada: nunca o que um
 * webhook alega, sempre uma consulta GET direta. O que este arquivo cobre é
 * a lógica em volta dessa chamada, que não depende de rede.
 */
class ClicksignSignatureCheckerTest extends TestCase
{
    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    private function createAdhesion(): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        return (int)$adhesion->id;
    }

    private function addAttempt(int $adhesionId, array $overrides = []): ClicksignData
    {
        $table = $this->table('ClicksignDatas');
        $entity = $table->newEntity($overrides + [
            'adhesion_initial_data_id' => $adhesionId,
            'envelope_id' => 'env-' . $adhesionId,
            'attempt' => 1,
            'status' => ClicksignData::STATUS_SENT,
        ]);
        $table->saveOrFail($entity);

        return $entity;
    }

    public function testWithNoAttemptAtAllNothingIsFound(): void
    {
        $checker = new ClicksignSignatureChecker($this->table('ClicksignDatas'), $this->fakeClient([]));

        $this->assertSame(['found' => false], $checker->refreshLatestFor($this->createAdhesion()));
    }

    /**
     * Uma tentativa cancelada nunca é revalidada como assinada: o envelope
     * corrente é o que importa, e o cancelado já não representa mais nada em
     * curso.
     */
    public function testACanceledAttemptIsNeverRecheckedAgainstTheApi(): void
    {
        $attempt = $this->addAttempt($this->createAdhesion(), [
            'status' => ClicksignData::STATUS_CANCELED,
            'canceled_at' => new \Cake\I18n\DateTime(),
        ]);

        // Um cliente que lançaria exceção se fosse de fato chamado: prova que
        // o caminho de cancelada nunca bate na Clicksign.
        $checker = new ClicksignSignatureChecker($this->table('ClicksignDatas'), $this->explodingClient());

        $result = $checker->refresh($attempt);

        $this->assertTrue($result['found']);
        $this->assertFalse($result['signed']);
    }

    public function testAClosedEnvelopeMarksTheAttemptAsSigned(): void
    {
        $adhesionId = $this->createAdhesion();
        $attempt = $this->addAttempt($adhesionId);

        $checker = new ClicksignSignatureChecker(
            $this->table('ClicksignDatas'),
            $this->fakeClient(['status' => 'closed'])
        );

        $result = $checker->refresh($attempt);

        $this->assertTrue($result['found']);
        $this->assertTrue($result['signed']);

        $reloaded = $this->table('ClicksignDatas')->get($attempt->id);
        $this->assertSame(ClicksignData::STATUS_SIGNED, $reloaded->status);
        $this->assertNotNull($reloaded->signed_at);
    }

    public function testARunningEnvelopeIsNotSigned(): void
    {
        $adhesionId = $this->createAdhesion();
        $attempt = $this->addAttempt($adhesionId);

        $checker = new ClicksignSignatureChecker(
            $this->table('ClicksignDatas'),
            $this->fakeClient(['status' => 'running'])
        );

        $result = $checker->refresh($attempt);

        $this->assertTrue($result['found']);
        $this->assertFalse($result['signed']);

        $this->assertSame(ClicksignData::STATUS_SENT, $this->table('ClicksignDatas')->get($attempt->id)->status);
    }

    public function testAnEnvelopeGoneFromClicksignIsNotFound(): void
    {
        $adhesionId = $this->createAdhesion();
        $attempt = $this->addAttempt($adhesionId);

        $checker = new ClicksignSignatureChecker($this->table('ClicksignDatas'), $this->throwingClient());

        $this->assertSame(['found' => false], $checker->refresh($attempt));
    }

    /**
     * Um cliente fake mínimo, sem tocar a rede: devolve a resposta de
     * getEnvelope() dada, ignorando qualquer outra chamada.
     */
    private function fakeClient(array $attributes): \App\Services\ClicksignService
    {
        return new class ($attributes) extends \App\Services\ClicksignService {
            public function __construct(private array $attributes)
            {
            }

            public function forAdhesion(?int $adhesionId): static
            {
                return $this;
            }

            public function getEnvelope(string $key): array
            {
                return ['data' => ['id' => $key, 'attributes' => $this->attributes]];
            }
        };
    }

    private function throwingClient(): \App\Services\ClicksignService
    {
        return new class () extends \App\Services\ClicksignService {
            public function __construct()
            {
            }

            public function forAdhesion(?int $adhesionId): static
            {
                return $this;
            }

            public function getEnvelope(string $key): array
            {
                throw new \Exception('Erro Clicksign. Status: 404.');
            }
        };
    }

    private function explodingClient(): \App\Services\ClicksignService
    {
        return new class () extends \App\Services\ClicksignService {
            public function __construct()
            {
            }

            public function forAdhesion(?int $adhesionId): static
            {
                throw new \Exception('não deveria ter sido chamado para uma tentativa cancelada');
            }
        };
    }
}
