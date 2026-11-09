<?php

declare(strict_types=1);

namespace App\Form;

use App\Services\BenefitEntryAge;
use App\Services\PlanProjection;
use App\Services\PlanQuote;
use App\Services\PlanSimulator;
use App\Utility\Money;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use DateTimeImmutable;

/**
 * A simulação avulsa do admin (Admin/Simulations): um plano para mandar ao
 * cliente em PDF sem criar adesão. Não grava nada.
 *
 * Valida com as mesmas regras de uma adesão -- piso do total, piso de cada
 * risco, faixa da idade de entrada --, para o cliente não receber uma
 * simulação que não poderia virar adesão. Os capitais segurados da tela não
 * são lidos: quote() os refaz a partir das contribuições, e o PDF não sai
 * com um capital desatualizado ou adulterado.
 */
class PlanSimulationForm extends Form
{
    /**
     * Cada risco: a flag, o campo da contribuição e a chave do piso em
     * PlanParameter.
     */
    private const RISKS = [
        'has_survivors_pension' => [
            'contribution' => 'monthly_survivors_pension_contribution',
            'risk' => 'survivors_pension',
        ],
        'has_disability_retirement' => [
            'contribution' => 'monthly_disability_retirement_contribution',
            'risk' => 'disability_retirement',
        ],
    ];

    public function __construct(private readonly PlanSimulator $simulator)
    {
        parent::__construct();
    }

    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema
            ->addField('name', 'string')
            ->addField('birth_date', 'date')
            ->addField('benefit_entry_age', 'integer')
            ->addField('has_survivors_pension', 'boolean')
            ->addField('has_disability_retirement', 'boolean')
            ->addField('monthly_retirement_contribution', 'decimal')
            ->addField('monthly_survivors_pension_contribution', 'decimal')
            ->addField('monthly_disability_retirement_contribution', 'decimal');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $parameters = $this->simulator->parameters();

        $validator
            ->requirePresence('name')
            ->notEmptyString('name', 'Informe o nome do cliente.');

        $validator
            ->requirePresence('birth_date')
            ->notEmptyDate('birth_date', 'Informe a data de nascimento.')
            ->date('birth_date', ['ymd'], 'Data de nascimento inválida.')
            // Data malformada já foi recusada por date(); as duas regras
            // abaixo só olham uma data válida.
            ->add('birth_date', 'notInTheFuture', [
                'rule' => fn($value, array $context): bool => $this->validBirthDate($context['data']) === null
                    || $value <= date('Y-m-d'),
                'message' => 'A data de nascimento não pode estar no futuro.',
            ])
            ->add('birth_date', 'beforeRetirement', [
                'rule' => fn($value, array $context): bool => $this->validBirthDate($context['data']) === null
                    || PlanSimulator::ageOn($value) < PlanProjection::RETIREMENT_AGE,
                'message' => 'O cliente já atingiu a idade máxima de entrada em benefício ('
                    . PlanProjection::RETIREMENT_AGE . ' anos).',
            ]);

        $validator
            ->requirePresence('benefit_entry_age')
            ->notEmptyString('benefit_entry_age', 'Informe a idade para entrada em benefício.')
            ->integer('benefit_entry_age', 'Informe a idade em anos.')
            ->add('benefit_entry_age', 'withinRange', [
                'rule' => function ($value, array $context): bool|string {
                    return BenefitEntryAge::error($value, $this->validBirthDate($context['data'])) ?? true;
                },
            ]);

        $validator
            ->requirePresence('monthly_retirement_contribution')
            ->add('monthly_retirement_contribution', 'notNegative', [
                'rule' => fn($value): bool => $this->money($value) >= 0,
                'message' => 'A contribuição de aposentadoria não pode ser negativa.',
            ])
            // O total não é um campo do formulário (a tela só soma as três
            // contribuições), então o piso dele aparece na aposentadoria,
            // que é a parte que o admin baixa para chegar no total.
            ->add('monthly_retirement_contribution', 'minimumTotal', [
                'rule' => fn($value, array $context): bool|string => $parameters->minimumContributionError(
                    array_sum($this->contributions($context['data']))
                ) ?? true,
            ]);

        foreach (self::RISKS as $flag => $risk) {
            $validator->add($risk['contribution'], 'aboveFloor', [
                'rule' => function ($value, array $context) use ($parameters, $flag, $risk): bool|string {
                    if (!$this->contracted($context['data'], $flag)) {
                        return true;
                    }

                    return $parameters->riskFloorError($risk['risk'], $this->money($value)) ?? true;
                },
            ]);
        }

        return $validator;
    }

    /**
     * O plano validado, com os capitais segurados refeitos pela procedure.
     */
    public function quote(): PlanQuote
    {
        $data = $this->getData();
        [$retirement, $survivorsPension, $disabilityRetirement] = $this->contributions($data);
        [$survivorsPensionCapital, $disabilityRetirementCapital] = $this->simulator->riskInsuredCapital(
            $data['birth_date'],
            $survivorsPension,
            $disabilityRetirement,
        );

        return new PlanQuote(
            clientName: trim((string)$data['name']),
            birthDate: $data['birth_date'],
            benefitEntryAge: (int)$data['benefit_entry_age'],
            retirementContribution: $retirement,
            hasSurvivorsPension: $this->contracted($data, 'has_survivors_pension'),
            survivorsPensionContribution: $survivorsPension,
            survivorsPensionInsuredCapital: $survivorsPensionCapital,
            hasDisabilityRetirement: $this->contracted($data, 'has_disability_retirement'),
            disabilityRetirementContribution: $disabilityRetirement,
            disabilityRetirementInsuredCapital: $disabilityRetirementCapital,
        );
    }

    /**
     * Se o risco existe. Abaixo da idade mínima não há risco, mesmo com a
     * flag ligada (PlanSimulator::effectiveRisks()).
     *
     * @param array<string, mixed> $data
     */
    private function contracted(array $data, string $flag): bool
    {
        $birthDate = $this->validBirthDate($data);

        if (empty($data[$flag]) || $birthDate === null) {
            return false;
        }

        [$survivorsPension, $disabilityRetirement] = $this->simulator->effectiveRisks($birthDate);

        return $flag === 'has_survivors_pension' ? $survivorsPension : $disabilityRetirement;
    }

    /**
     * Risco não contratado conta como zero, seja qual for o valor que veio.
     *
     * @param array<string, mixed> $data
     * @return array{0: float, 1: float, 2: float} [aposentadoria, morte, invalidez]
     */
    private function contributions(array $data): array
    {
        $risk = fn(string $flag): float => $this->contracted($data, $flag)
            ? $this->money($data[self::RISKS[$flag]['contribution']] ?? null)
            : 0.0;

        return [
            $this->money($data['monthly_retirement_contribution'] ?? null),
            $risk('has_survivors_pension'),
            $risk('has_disability_retirement'),
        ];
    }

    private function money(mixed $value): float
    {
        return (float)Money::parse($value);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validBirthDate(array $data): ?string
    {
        $birthDate = (string)($data['birth_date'] ?? '');
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);

        return $parsed !== false && $parsed->format('Y-m-d') === $birthDate ? $birthDate : null;
    }
}
