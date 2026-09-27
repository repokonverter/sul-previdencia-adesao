<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Tela única dos parâmetros do plano: abre com os valores atuais, grava os
 * novos, e recusa uma combinação inválida sem gravar nada.
 */
class PlanParametersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.PlanParameters',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->session([
            'Auth' => TableRegistry::getTableLocator()->get('Users')->get(1),
        ]);
    }

    private function current(): \App\Model\Entity\PlanParameter
    {
        /** @var \App\Model\Entity\PlanParameter */
        return TableRegistry::getTableLocator()->get('PlanParameters')
            ->find()
            ->orderBy(['id' => 'ASC'])
            ->firstOrFail();
    }

    public function testRequiresAuthentication(): void
    {
        $this->session(['Auth' => null]);

        $this->get('/admin/plan-parameters/edit');

        $this->assertRedirectContains('/admin/users/login');
    }

    public function testShowsTheCurrentValues(): void
    {
        $this->get('/admin/plan-parameters/edit');

        $this->assertResponseOk();
        $this->assertResponseContains('16.00');
        $this->assertResponseContains('Parâmetros do Plano');
    }

    public function testSavesNewValues(): void
    {
        $this->post('/admin/plan-parameters/edit', [
            'minimum_monthly_contribution' => '150.00',
            'survivors_pension_percent' => '18.00',
            'disability_retirement_percent' => '12.00',
            'survivors_pension_floor' => '27.00',
            'disability_retirement_floor' => '18.00',
        ]);

        $this->assertRedirect(['action' => 'edit']);

        $parameters = $this->current();

        $this->assertSame(150.0, $parameters->minimumMonthlyContribution());
        $this->assertSame(0.18, $parameters->survivorsPensionRate());
        $this->assertSame(27.0, $parameters->survivorsPensionFloor());
    }

    public function testRejectsAnImpossibleFloorWithoutSaving(): void
    {
        $this->post('/admin/plan-parameters/edit', [
            'minimum_monthly_contribution' => '100.00',
            'survivors_pension_percent' => '16.00',
            'disability_retirement_percent' => '10.00',
            // 16% de R$ 100,00 dá R$ 16,00: um piso de R$ 50,00 nunca seria
            // alcançado pela própria fórmula.
            'survivors_pension_floor' => '50.00',
            'disability_retirement_floor' => '10.00',
        ]);

        $this->assertResponseOk();
        $this->assertSame(16.0, $this->current()->survivorsPensionFloor());
    }
}
