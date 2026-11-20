<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Middleware\BenefitPlanMiddleware;
use App\Services\BenefitPlan;
use App\Services\PlanSimulator;
use Cake\Controller\Controller;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');


        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    /**
     * O plano de benefícios do host da requisição (ver BenefitPlanMiddleware).
     *
     * O admin responde em qualquer host e não usa isto: lá o plano é o da
     * adesão, ou o escolhido na tela.
     *
     * @throws \Cake\Http\Exception\NotFoundException Host sem plano.
     */
    protected function benefitPlan(): BenefitPlan
    {
        $plan = $this->request->getAttribute(BenefitPlanMiddleware::ATTRIBUTE);

        if (!$plan instanceof BenefitPlan) {
            throw new NotFoundException();
        }

        return $plan;
    }

    /**
     * O simulador com os parâmetros vigentes de um plano -- por padrão, o do
     * host. Usado pelo simulador público, pela aba Plano da adesão e pela
     * simulação avulsa do admin.
     */
    protected function planSimulator(?BenefitPlan $plan = null): PlanSimulator
    {
        $plan ??= $this->benefitPlan();

        return new PlanSimulator(
            ConnectionManager::get('default'),
            $this->fetchTable('PlanParameters')->forPlan($plan->slug)
        );
    }

    /**
     * Link de adesão (pagamento, retomada) aberto no host do outro plano:
     * manda para o mesmo caminho no host certo, em vez de mostrar a adesão
     * com o nome e os parâmetros do plano errado. Nulo quando já está no
     * host certo -- ou no fallback de desenvolvimento, que não tem host de
     * plano para onde ir.
     */
    protected function redirectToAdhesionHost(BenefitPlan $adhesionPlan): ?Response
    {
        $uri = $this->request->getUri();
        $requestPlan = $this->benefitPlan();

        if ($requestPlan->slug === $adhesionPlan->slug || $requestPlan->host() !== strtolower($uri->getHost())) {
            return null;
        }

        $query = $uri->getQuery();

        return $this->redirect($adhesionPlan->baseUrl . $uri->getPath() . ($query === '' ? '' : '?' . $query));
    }
}
