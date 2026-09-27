<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;

class AdhesionPlansTable extends AppTable
{
    /**
     * Os dois riscos, com o campo da contribuição de cada um e o parâmetro
     * que guarda o seu piso.
     */
    private const RISKS = [
        'has_survivors_pension' => [
            'contribution' => 'monthly_survivors_pension_contribution',
            'floor' => 'survivors_pension_floor',
            'label' => 'pensão por morte',
        ],
        'has_disability_retirement' => [
            'contribution' => 'monthly_disability_retirement_contribution',
            'floor' => 'disability_retirement_floor',
            'label' => 'aposentadoria por invalidez',
        ],
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('adhesion_plans');
        $this->belongsTo('AdhesionInitialDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['adhesion_initial_data_id'], 'AdhesionInitialDatas'), ['errorField' => 'adhesion_initial_data_id']);

        $rules->add(
            fn(EntityInterface $plan): bool => $this->contributionsReachTheirFloor($plan),
            'contributionsAboveFloor'
        );

        $rules->add(
            fn(EntityInterface $plan): bool => $this->healthDeclarationCoversTheRisks($plan),
            'healthDeclarationRequired',
            [
                'errorField' => 'has_survivors_pension',
                'message' => 'Esta adesão não tem Declaração Pessoal de Saúde. Para incluir risco, '
                    . 'o proponente precisa preenchê-la.',
            ]
        );

        return $rules;
    }

    /**
     * Piso de cada risco contratado.
     *
     * Pela fórmula o piso nunca é alcançado — 16% de qualquer contribuição a
     * partir do mínimo já passa de R$ 16 — então na prática ele limita o
     * quanto o admin pode baixar o valor à mão, que é o único caminho capaz de
     * produzir um valor menor.
     *
     * É regra de aplicação, e não validação, porque depende do estado final da
     * entidade: o formulário público não manda mais as flags de risco, e um
     * validador, que só enxerga os dados do patch, concluiria que todo risco
     * está contratado e cobraria piso de risco removido.
     */
    private function contributionsReachTheirFloor(EntityInterface $plan): bool
    {
        $parameters = TableRegistry::getTableLocator()->get('PlanParameters')->current();
        $withinFloor = true;

        foreach (self::RISKS as $has => $risk) {
            $contribution = $plan->get($risk['contribution']);

            // Contribuição ainda em branco não é contribuição rebaixada: o
            // piso guarda o quanto o admin pode baixar um valor, e recusar a
            // gravação de um plano incompleto só travaria o preenchimento.
            if (!$plan->get($has) || $contribution === null || $contribution === '') {
                continue;
            }

            $floor = (float)$parameters->get($risk['floor']);

            if ((float)$contribution >= $floor) {
                continue;
            }

            $plan->setError($risk['contribution'], [
                'aboveFloor' => 'A contribuição de ' . $risk['label'] . ' não pode ser menor que R$ '
                    . number_format($floor, 2, ',', '.') . '.',
            ]);

            $withinFloor = false;
        }

        return $withinFloor;
    }

    /**
     * Impede religar um risco numa adesão que nunca teve declaração de saúde.
     *
     * Sem isso, marcar o risco de volta faz a proposta sair com as onze
     * perguntas respondidas por ninguém — o proponente assinaria negando
     * cardiopatia, câncer e cirurgias que nunca lhe foram perguntadas. As
     * saídas são mandar o link de retomada apontando para a declaração, ou
     * preenchê-la na própria edição.
     *
     * Só vale para plano que já existe: no formulário público o plano nasce
     * com os dois riscos e a declaração vem na etapa seguinte, então exigir a
     * declaração aqui travaria toda adesão nova.
     */
    private function healthDeclarationCoversTheRisks(EntityInterface $plan): bool
    {
        if ($plan->isNew()) {
            return true;
        }

        $turnedOn = false;

        foreach (array_keys(self::RISKS) as $field) {
            if ($plan->isDirty($field) && $plan->get($field) && !$plan->getOriginal($field)) {
                $turnedOn = true;
            }
        }

        if (!$turnedOn) {
            return true;
        }

        return TableRegistry::getTableLocator()->get('AdhesionProponentStatements')
            ->exists(['adhesion_initial_data_id' => $plan->get('adhesion_initial_data_id')]);
    }
}
