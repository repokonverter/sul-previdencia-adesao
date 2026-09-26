<?php

declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\Money;
use Cake\TestSuite\TestCase;

class MoneyTest extends TestCase
{
    /**
     * As duas grafias que o próprio formulário produz para o mesmo valor: a
     * carga inicial do template, com ponto decimal, e o recálculo, em pt-BR.
     */
    public function testParsesBothSpellingsTheFormProduces(): void
    {
        $cases = [
            'carga inicial do template' => ['370.00', '370.00'],
            'depois de recalcular' => ['370,00', '370.00'],
            'milhar em pt-BR' => ['1.234,56', '1234.56'],
            'milhar com ponto decimal' => ['19566.00', '19566.00'],
            'inteiro' => ['500', '500'],
            'com espaços' => [' 370,00 ', '370.00'],
            'vazio' => ['', null],
            'nulo' => [null, null],
        ];

        foreach ($cases as $name => [$input, $expected]) {
            $this->assertSame($expected, Money::parse($input), $name);
        }
    }

    /**
     * O erro concreto que isto corrige: assumir a grafia brasileira apagava o
     * ponto de "370.00" e gravava cem vezes o valor.
     */
    public function testTheOldParsingMultipliedByOneHundred(): void
    {
        $old = str_replace(',', '.', str_replace('.', '', '370.00'));

        $this->assertSame('37000', $old);
        $this->assertSame('370.00', Money::parse('370.00'));
    }
}
