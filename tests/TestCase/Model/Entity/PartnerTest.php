<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Partner;
use Cake\TestSuite\TestCase;

class PartnerTest extends TestCase
{
    public function testDeclarationTextsFallsBackToDefaultsWhenBlank(): void
    {
        $partner = new Partner(['declaration_title' => null, 'declaration_institution_name' => null, 'declaration_body' => null]);

        $this->assertSame([
            'title' => Partner::DEFAULT_DECLARATION_TITLE,
            'institutionName' => Partner::DEFAULT_DECLARATION_INSTITUTION_NAME,
            'body' => Partner::DEFAULT_DECLARATION_BODY,
        ], $partner->declarationTexts());
    }

    public function testDeclarationTextsUsesEachFieldIndependently(): void
    {
        $partner = new Partner([
            'declaration_title' => 'DECLARAÇÃO DE COMPARECIMENTO',
            'declaration_institution_name' => null,
            'declaration_body' => 'Texto específico do vínculo.',
        ]);

        $texts = $partner->declarationTexts();

        $this->assertSame('DECLARAÇÃO DE COMPARECIMENTO', $texts['title']);
        $this->assertSame(Partner::DEFAULT_DECLARATION_INSTITUTION_NAME, $texts['institutionName']);
        $this->assertSame('Texto específico do vínculo.', $texts['body']);
    }
}
