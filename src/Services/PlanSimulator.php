<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Entity\PlanParameter;
use Cake\Database\Connection;
use DateTimeImmutable;

/**
 * Decide quanto vai para cada risco e chama a procedure `simulacao_previdencia`.
 *
 * A divisão de responsabilidade é a razão de existir desta classe: a procedure
 * é calculadora atuarial pura (tabela de custo por idade, tetos, capital,
 * saldo, benefício) e recebe os valores de risco prontos, em reais. Toda regra
 * de negócio sobre *quanto* cada risco recebe mora aqui, com uma fonte de
 * verdade só — antes as taxas estavam cravadas em PL/pgSQL, onde nenhum teste
 * de PHP as alcançava e mudá-las exigia migration.
 */
class PlanSimulator
{
    /**
     * Abaixo desta idade não há risco algum, independentemente do que for
     * pedido. A regra vivia em SimulatorController, junto da checagem de
     * corretor; vive agora em effectiveRisks(), por onde todo chamador passa.
     */
    public const MINIMUM_RISK_AGE = 16;

    public function __construct(
        private readonly Connection $connection,
        private readonly PlanParameter $parameters,
    ) {
    }

    public function parameters(): PlanParameter
    {
        return $this->parameters;
    }

    public static function ageOn(string $birthDate): int
    {
        return (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($birthDate))->y;
    }

    /**
     * Quais riscos a adesão de fato tem, depois das regras que não dependem de
     * escolha. O chamador precisa disso para a tela: a simulação de um menor
     * de 16 já vem com contribuição zero, mas quem monta a página ainda tem
     * que saber que é "não contratado", e não "contratado por R$ 0,00".
     *
     * @return array{0: bool, 1: bool} [morte, invalidez]
     */
    public function effectiveRisks(
        string $birthDate,
        bool $hasSurvivorsPension = true,
        bool $hasDisabilityRetirement = true,
    ): array {
        if (static::ageOn($birthDate) < self::MINIMUM_RISK_AGE) {
            return [false, false];
        }

        return [$hasSurvivorsPension, $hasDisabilityRetirement];
    }

    /**
     * Quanto vai para cada risco, em reais.
     *
     * Arredonda para centavos porque contribuição é dinheiro: sem isso os três
     * valores exibidos podiam não somar o total (R$ 333,33 rendia 53,33 +
     * 33,33 + 246,66 = R$ 333,32 na tela). Como a aposentadoria recebe o que
     * sobra, arredondar só os riscos mantém a soma exata.
     *
     * @return array{0: float, 1: float} [morte, invalidez]
     */
    public function riskContributions(
        string $birthDate,
        float $monthlyContribution,
        bool $hasSurvivorsPension = true,
        bool $hasDisabilityRetirement = true,
    ): array {
        [$survivorsPension, $disabilityRetirement] = $this->effectiveRisks(
            $birthDate,
            $hasSurvivorsPension,
            $hasDisabilityRetirement,
        );

        return [
            $survivorsPension ? round($monthlyContribution * $this->parameters->survivorsPensionRate(), 2) : 0.0,
            $disabilityRetirement ? round($monthlyContribution * $this->parameters->disabilityRetirementRate(), 2) : 0.0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>> uma linha por taxa de rentabilidade
     */
    public function simulate(
        string $birthDate,
        float $monthlyContribution,
        bool $hasSurvivorsPension = true,
        bool $hasDisabilityRetirement = true,
    ): array {
        [$survivorsPension, $disabilityRetirement] = $this->riskContributions(
            $birthDate,
            $monthlyContribution,
            $hasSurvivorsPension,
            $hasDisabilityRetirement,
        );

        return $this->connection
            ->execute(
                'SELECT *
                FROM simulacao_previdencia(:date, :value, :survivorsPension, :disabilityRetirement)',
                [
                    'date' => $birthDate,
                    'value' => $monthlyContribution,
                    'survivorsPension' => $survivorsPension,
                    'disabilityRetirement' => $disabilityRetirement,
                ],
                [
                    'value' => 'decimal',
                    'survivorsPension' => 'decimal',
                    'disabilityRetirement' => 'decimal',
                ],
            )
            ->fetchAll('assoc');
    }

    public function benefitEntryAge(string $birthDate): int
    {
        $age = static::ageOn($birthDate);

        return $age <= 55 ? 65 : $age + 10;
    }
}
