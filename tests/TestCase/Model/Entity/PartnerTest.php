<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Partner;
use Cake\TestSuite\TestCase;

class PartnerTest extends TestCase
{
    public function testAssociationDeclarationDataReadsCompanyFields(): void
    {
        $partner = new Partner(['company_name' => 'Associação Catarinense de Tecnologia', 'company_cnpj' => '12.345.678/0001-99']);

        $this->assertSame([
            'companyName' => 'Associação Catarinense de Tecnologia',
            'companyCnpj' => '12.345.678/0001-99',
        ], $partner->associationDeclarationData());
    }

    public function testAssociationDeclarationDataIsNullWhenUnfilled(): void
    {
        $partner = new Partner(['company_name' => null, 'company_cnpj' => null]);

        $this->assertSame(['companyName' => null, 'companyCnpj' => null], $partner->associationDeclarationData());
    }
}
