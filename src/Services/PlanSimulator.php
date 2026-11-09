<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Entity\AdhesionPlan;
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

    /**
     * Quantos meses de contribuição a procedure capitaliza até os 65 anos.
     *
     * O gráfico do simulador precisa do mesmo prazo do card, ou a curva não
     * termina no saldo que a tela mostra ao lado. A expressão é copiada da
     * procedure e roda no Postgres de propósito: refeita em PHP, divergiria
     * no "hoje" (CURRENT_DATE do banco) e no fim de mês de AGE() -- quem nasceu
     * num dia 31 ou em 29/02 ganharia ou perderia um mês.
     */
    public function monthsUntilRetirement(string $birthDate): int
    {
        $row = $this->connection
            ->execute(
                "SELECT EXTRACT(YEAR FROM AGE(CAST(:date AS date) + INTERVAL '65 years', CURRENT_DATE)) * 12
                    + EXTRACT(MONTH FROM AGE(CAST(:date AS date) + INTERVAL '65 years', CURRENT_DATE)) - 1 AS months",
                ['date' => $birthDate],
            )
            ->fetch('assoc');

        return max(0, (int)$row['months']);
    }

    /**
     * As linhas da procedure para contribuições já decididas -- as gravadas
     * na adesão, inclusive as ajustadas à mão pelo admin --, sem passar pela
     * divisão padrão de riskContributions(). A procedure põe na aposentadoria
     * o total menos os riscos, então mandar a soma dos três reproduz
     * exatamente a contribuição de aposentadoria informada.
     *
     * @return array<int, array<string, mixed>> uma linha por taxa de rentabilidade
     */
    public function simulateContributions(
        string $birthDate,
        float $retirementContribution,
        float $survivorsPensionContribution,
        float $disabilityRetirementContribution,
    ): array {
        return $this->connection
            ->execute(
                'SELECT *
                FROM simulacao_previdencia(:date, :value, :survivorsPension, :disabilityRetirement)',
                [
                    'date' => $birthDate,
                    'value' => $retirementContribution + $survivorsPensionContribution + $disabilityRetirementContribution,
                    'survivorsPension' => $survivorsPensionContribution,
                    'disabilityRetirement' => $disabilityRetirementContribution,
                ],
                [
                    'value' => 'decimal',
                    'survivorsPension' => 'decimal',
                    'disabilityRetirement' => 'decimal',
                ],
            )
            ->fetchAll('assoc');
    }

    /**
     * As contribuições do plano gravado. Risco desmarcado entra como zero
     * mesmo que a coluna de contribuição tenha ficado com valor: é a flag
     * que diz se o risco existe.
     *
     * @return array{0: float, 1: float, 2: float} [aposentadoria, morte, invalidez]
     */
    public static function savedPlanContributions(AdhesionPlan $plan): array
    {
        return [
            (float)$plan->monthly_retirement_contribution,
            $plan->has_survivors_pension === false ? 0.0 : (float)$plan->monthly_survivors_pension_contribution,
            $plan->has_disability_retirement === false ? 0.0 : (float)$plan->monthly_disability_retirement_contribution,
        ];
    }

    /**
     * simulateContributions() com o plano gravado. É o que o link de
     * retomada mostra: a mesma conta da aba Plano do admin, para as duas
     * telas não divergirem.
     *
     * @return array<int, array<string, mixed>>
     */
    public function simulateSavedPlan(string $birthDate, AdhesionPlan $plan): array
    {
        return $this->simulateContributions($birthDate, ...static::savedPlanContributions($plan));
    }

    public function project(
        string $birthDate,
        float $retirementContribution,
        float $survivorsPensionContribution,
        float $disabilityRetirementContribution,
    ): PlanProjection {
        return PlanProjection::fromProcedureRows(
            $this->simulateContributions(
                $birthDate,
                $retirementContribution,
                $survivorsPensionContribution,
                $disabilityRetirementContribution,
            ),
            $this->monthsUntilRetirement($birthDate),
        );
    }

    public function projectSavedPlan(string $birthDate, AdhesionPlan $plan): PlanProjection
    {
        return $this->project($birthDate, ...static::savedPlanContributions($plan));
    }

    public function projectQuote(PlanQuote $quote): PlanProjection
    {
        return $this->project(
            $quote->birthDate,
            $quote->retirementContribution,
            $quote->survivorsPensionContribution,
            $quote->disabilityRetirementContribution,
        );
    }

    /**
     * O "Recalcular" do admin, no editar da adesão e na simulação avulsa:
     * reparte um total novo sem desfazer o que o admin digitou.
     *
     * Risco contratado com contribuição já preenchida fica como está; risco
     * contratado ainda em zero recebe a fatia padrão de riskContributions();
     * risco desmarcado fica em zero. A aposentadoria leva o resto -- negativa
     * quando os riscos passam do total, e cabe ao chamador recusar.
     *
     * @return array{0: float, 1: float, 2: float} [aposentadoria, morte, invalidez]
     */
    public function rebalance(
        string $birthDate,
        float $totalContribution,
        bool $hasSurvivorsPension,
        float $survivorsPensionContribution,
        bool $hasDisabilityRetirement,
        float $disabilityRetirementContribution,
    ): array {
        [$defaultSurvivorsPension, $defaultDisabilityRetirement] = $this->riskContributions(
            $birthDate,
            $totalContribution,
            $hasSurvivorsPension,
            $hasDisabilityRetirement,
        );

        $keepOrDefault = fn(bool $contracted, float $typed, float $default): float => match (true) {
            !$contracted => 0.0,
            $typed > 0 => round($typed, 2),
            default => $default,
        };

        $survivorsPension = $keepOrDefault($hasSurvivorsPension, $survivorsPensionContribution, $defaultSurvivorsPension);
        $disabilityRetirement = $keepOrDefault($hasDisabilityRetirement, $disabilityRetirementContribution, $defaultDisabilityRetirement);

        return [
            round($totalContribution - $survivorsPension - $disabilityRetirement, 2),
            $survivorsPension,
            $disabilityRetirement,
        ];
    }

    /**
     * O capital segurado correspondente a uma contribuição de risco já
     * decidida -- não uma fatia recalculada do total, a contribuição exata
     * que o admin digitou. A procedure aplica a mesma fórmula (custo
     * unitário por idade, teto) independentemente de onde a contribuição
     * veio, então basta chamá-la com os dois valores de risco na mão e
     * ignorar o resto da linha: aposentadoria/saldo/benefício não entram
     * nessa conta, e as três taxas de rentabilidade do loop interno da
     * procedure devolvem a mesma cobertura, então uma linha basta.
     *
     * @return array{0: float, 1: float} [capital morte, capital invalidez]
     */
    public function riskInsuredCapital(
        string $birthDate,
        float $survivorsPensionContribution,
        float $disabilityRetirementContribution,
    ): array {
        $row = $this->connection
            ->execute(
                'SELECT cobertura_morte, cobertura_invalidez
                FROM simulacao_previdencia(:date, :value, :survivorsPension, :disabilityRetirement)
                LIMIT 1',
                [
                    'date' => $birthDate,
                    'value' => $survivorsPensionContribution + $disabilityRetirementContribution,
                    'survivorsPension' => $survivorsPensionContribution,
                    'disabilityRetirement' => $disabilityRetirementContribution,
                ],
                [
                    'value' => 'decimal',
                    'survivorsPension' => 'decimal',
                    'disabilityRetirement' => 'decimal',
                ],
            )
            ->fetch('assoc');

        return [(float)$row['cobertura_morte'], (float)$row['cobertura_invalidez']];
    }
}
