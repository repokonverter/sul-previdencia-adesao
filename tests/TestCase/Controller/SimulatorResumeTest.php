<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O link que devolve a proposta ao proponente.
 */
class SimulatorResumeTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.PlanParameters',
    ];

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    private function createAdhesion(): \App\Model\Entity\AdhesionInitialData
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        $this->table('AdhesionPersonalDatas')->saveOrFail(
            $this->table('AdhesionPersonalDatas')->newEntity([
                'adhesion_initial_data_id' => $adhesion->id,
                'plan_for' => 'Titular',
                'name' => 'Fulano de Tal',
                'cpf' => '123.456.789-09',
                'birth_date' => '1985-03-10',
                'nacionality' => 'Brasileira',
            ])
        );

        return $adhesions->get($adhesion->id);
    }

    public function testAValidLinkReopensTheProposalFilledIn(): void
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $this->createAdhesion();
        $token = $adhesions->issueResumeToken($adhesion, 7, 'addressData');

        $this->get("/proposta/$token");

        $this->assertResponseOk();
        // O blob que repopula o formulário chega na página.
        $this->assertResponseContains('"initialDataId":' . $adhesion->id);
        $this->assertResponseContains('"step":"addressData"');
        $this->assertResponseContains('Fulano de Tal');
        $this->assertResponseContains('1985-03-10');
    }

    /**
     * Expirado e inexistente dizem coisas diferentes: um existiu e o prazo
     * acabou, o outro nunca existiu. Um 404 seco faria a pessoa achar que o
     * sistema perdeu os dados dela.
     */
    public function testAnExpiredLinkSaysSoAndReassures(): void
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $this->createAdhesion();
        $token = $adhesions->issueResumeToken($adhesion, 7);

        $adhesions->saveOrFail($adhesions->patchEntity($adhesions->get($adhesion->id), [
            'resume_token_expires_at' => DateTime::now()->subDays(1),
        ]));

        $this->get("/proposta/$token");

        $this->assertResponseOk();
        $this->assertResponseContains('Este link expirou');
        $this->assertResponseContains('continuam guardados');
    }

    public function testAnUnknownLinkSaysSoWithoutRevealingAnything(): void
    {
        $this->get('/proposta/naoexiste');

        $this->assertResponseOk();
        $this->assertResponseContains('Link não encontrado');
        $this->assertResponseNotContains('Fulano de Tal');
    }

    /**
     * Gerar de novo invalida o anterior. A rotação é na geração, e não no
     * envio, para o admin poder mandar o mesmo link por e-mail e por WhatsApp.
     */
    public function testIssuingANewLinkInvalidatesTheOldOne(): void
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $this->createAdhesion();

        $first = $adhesions->issueResumeToken($adhesion, 7);
        $second = $adhesions->issueResumeToken($adhesions->get($adhesion->id), 7);

        $this->assertNotSame($first, $second);

        $this->get("/proposta/$first");
        $this->assertResponseContains('Link não encontrado');

        $this->get("/proposta/$second");
        $this->assertResponseContains('"initialDataId":' . $adhesion->id);
    }

    public function testRevokingTheLinkClosesTheDoor(): void
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $this->createAdhesion();
        $token = $adhesions->issueResumeToken($adhesion, 7);

        $adhesions->revokeResumeToken($adhesions->get($adhesion->id));

        $this->get("/proposta/$token");

        $this->assertResponseContains('Link não encontrado');
    }

    /**
     * Token aleatório, e não derivado do id: um valor derivado torna a base
     * enumerável se o algoritmo vazar, e ids sequenciais tornam trivial gerar
     * candidatos. Duas adesões criadas em sequência recebem tokens sem
     * nenhuma relação entre si.
     */
    public function testTokensAreRandomRatherThanDerivedFromTheId(): void
    {
        $adhesions = $this->table('AdhesionInitialDatas');

        $first = $adhesions->issueResumeToken($this->createAdhesion(), 7);
        $second = $adhesions->issueResumeToken($this->createAdhesion(), 7);

        foreach ([$first, $second] as $token) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        }

        // Duas adesões consecutivas: um token derivado do id seria vizinho do
        // outro. Contar caracteres iguais na mesma posição é mais honesto que
        // comparar prefixos -- dois hex aleatórios coincidem no primeiro
        // caractere uma vez a cada dezesseis, e o teste piscaria.
        $equal = count(array_filter(
            str_split($first),
            fn(string $char, int $i): bool => $char === $second[$i],
            ARRAY_FILTER_USE_BOTH
        ));

        $this->assertLessThan(
            24,
            $equal,
            'tokens de duas adesões seguidas coincidiram demais para serem aleatórios'
        );
    }
}
