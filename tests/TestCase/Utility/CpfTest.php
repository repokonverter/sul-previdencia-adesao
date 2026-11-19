<?php

declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\Cpf;
use Cake\TestSuite\TestCase;

class CpfTest extends TestCase
{
    public function testAcceptsAValidCpfWithOrWithoutMask(): void
    {
        $this->assertTrue(Cpf::isValid('529.982.247-25'));
        $this->assertTrue(Cpf::isValid('52998224725'));
    }

    public function testRejectsWrongCheckDigitsAndRepeatedDigits(): void
    {
        $this->assertFalse(Cpf::isValid('529.982.247-24'));
        $this->assertFalse(Cpf::isValid('111.111.111-11'));
        $this->assertFalse(Cpf::isValid('1234'));
        $this->assertFalse(Cpf::isValid(null));
    }

    public function testNormalizeKeepsOnlyDigitsAndEmptyBecomesNull(): void
    {
        $this->assertSame('52998224725', Cpf::normalize('529.982.247-25'));
        $this->assertNull(Cpf::normalize(''));
        $this->assertNull(Cpf::normalize('.-'));
    }

    public function testFormat(): void
    {
        $this->assertSame('529.982.247-25', Cpf::format('52998224725'));
        $this->assertNull(Cpf::format(null));
    }
}
