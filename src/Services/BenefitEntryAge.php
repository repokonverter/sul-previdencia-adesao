<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Até onde o admin pode levar a idade de entrada em benefício.
 *
 * O teto é a idade em que a procedure para de capitalizar: entrar em
 * benefício depois dos 65 prometeria ao cliente um saldo que nenhuma conta do
 * plano projeta. O piso é a idade de hoje, porque não se entra em benefício
 * no passado.
 *
 * Só vale para o que o admin digita (editar/criar adesão e a simulação
 * avulsa). O fluxo público segue com PlanSimulator::benefitEntryAge(), que
 * dá idade + 10 a quem passou dos 55.
 */
final class BenefitEntryAge
{
    public const MAXIMUM = PlanProjection::RETIREMENT_AGE;

    /**
     * @param string|null $birthDate sem ela, só o teto é conferido
     */
    public static function error(mixed $entryAge, ?string $birthDate): ?string
    {
        if ($entryAge === null || $entryAge === '') {
            return null;
        }

        if ((int)$entryAge > self::MAXIMUM) {
            return 'A idade para entrada em benefício deve ser no máximo ' . self::MAXIMUM . ' anos.';
        }

        $age = $birthDate === null ? null : PlanSimulator::ageOn($birthDate);

        if ($age !== null && (int)$entryAge < $age) {
            return "A idade para entrada em benefício não pode ser menor que a idade atual ($age anos).";
        }

        return null;
    }
}
