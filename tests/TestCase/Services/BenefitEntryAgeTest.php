<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Services\BenefitEntryAge;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

class BenefitEntryAgeTest extends TestCase
{
    private function yearsAgo(int $years): string
    {
        return (new DateTimeImmutable("-$years years"))->format('Y-m-d');
    }

    public function testFromTheCurrentAgeUpToSixtyFive(): void
    {
        $this->assertNull(BenefitEntryAge::error(65, $this->yearsAgo(35)));
        $this->assertNull(BenefitEntryAge::error(35, $this->yearsAgo(35)));
    }

    public function testAboveSixtyFiveIsRefused(): void
    {
        $this->assertSame(
            'A idade para entrada em benefício deve ser no máximo 65 anos.',
            BenefitEntryAge::error('66', $this->yearsAgo(35))
        );
    }

    public function testBelowTheCurrentAgeIsRefused(): void
    {
        $this->assertSame(
            'A idade para entrada em benefício não pode ser menor que a idade atual (35 anos).',
            BenefitEntryAge::error(34, $this->yearsAgo(35))
        );
    }

    public function testWithoutBirthDateOnlyTheCeilingCounts(): void
    {
        $this->assertNull(BenefitEntryAge::error(18, null));
        $this->assertNotNull(BenefitEntryAge::error(70, null));
    }

    public function testBlankIsNotThisRulesBusiness(): void
    {
        $this->assertNull(BenefitEntryAge::error(null, $this->yearsAgo(35)));
        $this->assertNull(BenefitEntryAge::error('', $this->yearsAgo(35)));
    }
}
