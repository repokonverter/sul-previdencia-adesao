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
        $this->assertGreaterThan(0, $this->viewVariable('monthsUntilRetirement'));
    }

    private function addPlan(int $adhesionId, string $retirement, string $survivorsPension, string $disabilityRetirement): \App\Model\Entity\AdhesionPlan
    {
        $plans = $this->table('AdhesionPlans');
        $plan = $plans->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'benefit_entry_age' => 65,
            'monthly_retirement_contribution' => $retirement,
            'monthly_survivors_pension_contribution' => $survivorsPension,
            'survivors_pension_insured_capital' => '1000.00',
            'monthly_disability_retirement_contribution' => $disabilityRetirement,
            'disability_retirement_insured_capital' => '1000.00',
        ]);

        return $plans->saveOrFail($plan);
    }

    private function adminProjection(\App\Model\Entity\AdhesionPlan $plan): \App\Services\PlanProjection
    {
        return (new \App\Services\PlanSimulator(
            \Cake\Datasource\ConnectionManager::get('test'),
            $this->table('PlanParameters')->current()
        ))->projectSavedPlan('1985-03-10', $plan);
    }

    /**
     * Sem dados de pagamento, a retomada usava só a contribuição de
     * aposentadoria como se fosse o total: os cenários de 6/8/10% saíam
     * menores que os da aba Plano do admin para a mesma adesão.
     */
    public function testScenariosMatchTheAdminPlanTab(): void
    {
        $adhesion = $this->createAdhesion();
        $plan = $this->addPlan($adhesion->id, '740.00', '160.00', '100.00');
        $token = $this->table('AdhesionInitialDatas')->issueResumeToken($adhesion, 7, 'plan');

        $this->get("/proposta/$token");

        $this->assertResponseOk();
        $this->assertEqualsWithDelta(1000.0, $this->viewVariable('totalMonthlyContributionPlan'), 0.001);
        $this->assertSame(
            array_column($this->adminProjection($plan)->scenarios, 'balance'),
            array_map(fn(array $row): float => (float)$row['saldo_acumulado'], $this->viewVariable('simulations'))
        );
    }

    /**
     * Valor ajustado à mão pelo admin não segue a divisão 74/16/10: a
     * retomada mostra o que foi gravado, não a fórmula reaplicada ao total.
     */
    public function testAnAdminAdjustedPlanIsShownAsSaved(): void
    {
        $adhesion = $this->createAdhesion();
        $this->addPlan($adhesion->id, '850.00', '100.00', '50.00');
        $token = $this->table('AdhesionInitialDatas')->issueResumeToken($adhesion, 7, 'plan');

        $this->get("/proposta/$token");

        $simulation = $this->viewVariable('simulations')[1];
        $this->assertEqualsWithDelta(850.0, (float)$simulation['contribuicao_aposentadoria'], 0.001);
        $this->assertEqualsWithDelta(100.0, (float)$simulation['contribuicao_morte'], 0.001);
        $this->assertEqualsWithDelta(50.0, (float)$simulation['contribuicao_invalidez'], 0.001);
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
