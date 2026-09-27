<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\PlanParameter;
use Cake\Validation\Validator;
use RuntimeException;

class PlanParametersTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('plan_parameters');
        $this->setPrimaryKey('id');
    }

    /**
     * A linha única de parâmetros. A migration que cria a tabela já insere a
     * linha, e não existe tela de criar nem de excluir — se ela sumiu, algo
     * mexeu no banco à mão, e falhar alto é melhor do que simular valores
     * padrão e gravar adesões com números que ninguém configurou.
     */
    public function current(): PlanParameter
    {
        /** @var \App\Model\Entity\PlanParameter|null $parameters */
        $parameters = $this->find()->orderBy(['PlanParameters.id' => 'ASC'])->first();

        if ($parameters === null) {
            throw new RuntimeException(
                'Nenhuma linha em plan_parameters. Rode as migrations: a linha é criada por CreatePlanParameters.'
            );
        }

        return $parameters;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->decimal('minimum_monthly_contribution')
            ->greaterThan('minimum_monthly_contribution', 0, 'A contribuição mínima precisa ser maior que zero.');

        foreach (['survivors_pension' => 'pensão por morte', 'disability_retirement' => 'invalidez'] as $risk => $label) {
            $validator
                ->decimal($risk . '_percent')
                ->greaterThan($risk . '_percent', 0, "O percentual de $label precisa ser maior que zero.")
                ->lessThan($risk . '_percent', 100, "O percentual de $label precisa ser menor que 100.");

            $validator
                ->decimal($risk . '_floor')
                ->greaterThan($risk . '_floor', 0, "O mínimo de $label precisa ser maior que zero.");
        }

        // A aposentadoria recebe o que sobra depois dos dois riscos; somando
        // 100% ou mais, ela ficaria zerada ou negativa e a simulação passaria
        // a devolver saldo acumulado negativo sem erro nenhum.
        $validator->add('disability_retirement_percent', 'sumUnderOneHundred', [
            'rule' => fn($value, $context) => (float)$value + (float)($context['data']['survivors_pension_percent'] ?? 0) < 100,
            'message' => 'Os dois percentuais somados precisam ficar abaixo de 100%: a aposentadoria recebe o restante.',
        ]);

        // Um piso acima do que a própria taxa produz na contribuição mínima
        // seria contraditório: uma adesão no mínimo, calculada pela fórmula,
        // já nasceria violando o limite configurado para ela mesma.
        foreach (['survivors_pension' => 'pensão por morte', 'disability_retirement' => 'invalidez'] as $risk => $label) {
            $validator->add($risk . '_floor', 'reachableAtMinimum', [
                'rule' => function ($value, $context) use ($risk) {
                    $minimum = (float)($context['data']['minimum_monthly_contribution'] ?? 0);
                    $percent = (float)($context['data'][$risk . '_percent'] ?? 0);

                    return (float)$value <= round($minimum * $percent / 100, 2);
                },
                'message' => "O mínimo de $label não pode passar do que o percentual gera na contribuição mínima.",
            ]);
        }

        $validator
            ->integer('resume_link_days')
            ->greaterThan('resume_link_days', 0, 'A validade do link de retomada precisa ser de ao menos um dia.');

        return $validator;
    }
}
