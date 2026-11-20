<?php

declare(strict_types=1);

namespace App\Test\TestSuite;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use PHPUnit\Framework\Attributes\After;

/**
 * Um segundo plano de benefícios, só para o teste: o config/plans.php de
 * produção tem um plano só até o MaisPremium ir ao ar, e o que se testa aqui
 * é justamente a separação entre dois.
 *
 * Exige a fixture app.PlanParameters (a linha do PlenoPrev); a do segundo
 * plano é inserida aqui.
 */
trait SecondBenefitPlanTrait
{
    protected const SECOND_PLAN = 'maispremium';

    protected const SECOND_PLAN_HOST = 'maispremium.konverter.com.br';

    protected function configureSecondBenefitPlan(): void
    {
        Configure::write('BenefitPlans.' . self::SECOND_PLAN, [
            'name' => 'MaisPremium',
            'baseUrl' => 'https://' . self::SECOND_PLAN_HOST,
            'cnpj' => '99.888.777/0001-66',
            'cnpb' => '2026/0042-11',
            'loading' => '1%',
            'slogan' => 'Mais para o seu futuro',
            'proposalPrefix' => 'MPX',
            'txidPrefix' => 'MAISPREMIUM',
            'institution' => ['number' => '042', 'name' => 'INSTITUIDOR MP'],
            'insurer' => ['shortName' => 'Seguradora MP'] + Configure::read('BenefitPlans.plenoprev.insurer'),
            'broker' => Configure::read('BenefitPlans.plenoprev.broker'),
            'pix' => ['provider' => 'sicoob', 'config' => 'Sicoob'],
        ]);

        $parameters = TableRegistry::getTableLocator()->get('PlanParameters');
        $parameters->saveOrFail($parameters->newEntity([
            'minimum_monthly_contribution' => '250.00',
            'survivors_pension_percent' => '16.00',
            'disability_retirement_percent' => '10.00',
            'survivors_pension_floor' => '16.00',
            'disability_retirement_floor' => '10.00',
            'resume_link_days' => 7,
        ])
            // id explícito: a fixture insere o 1 sem avançar a sequência.
            ->set('id', 2)
            ->set('benefit_plan', self::SECOND_PLAN));
    }

    /**
     * BenefitPlanMiddleware troca o fullBaseUrl do Router (estático) quando
     * a requisição chega no host de um plano; sem desfazer, os testes
     * seguintes montariam URLs absolutas no host do segundo plano.
     */
    #[After]
    protected function forgetSecondBenefitPlan(): void
    {
        Configure::delete('BenefitPlans.' . self::SECOND_PLAN);
        Router::fullBaseUrl((string)Configure::read('App.fullBaseUrl'));
    }

    /**
     * As requisições seguintes do teste chegam neste host (null volta ao
     * padrão, "localhost"). Atribui em vez de usar configRequest(), que
     * acumula com array_merge_recursive e transformaria o HTTP_HOST de duas
     * chamadas num array.
     */
    protected function onHost(?string $host): void
    {
        if ($host === null) {
            unset($this->_request['environment']['HTTP_HOST']);

            return;
        }

        $this->_request['environment']['HTTP_HOST'] = $host;
    }

    protected function onSecondPlanHost(): void
    {
        $this->onHost(self::SECOND_PLAN_HOST);
    }
}
