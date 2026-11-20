<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BrokersTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class BrokersTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Partners',
        'app.Brokers',
    ];

    private BrokersTable $Brokers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Brokers = TableRegistry::getTableLocator()->get('Brokers');

        // A fixture grava ids fixos sem avançar a sequence; o próximo INSERT
        // tentaria o id 1.
        foreach (['partners', 'brokers'] as $table) {
            $this->Brokers->getConnection()
                ->execute("SELECT setval(pg_get_serial_sequence('$table', 'id'), (SELECT MAX(id) FROM $table))");
        }
    }

    public function testTokenIsGeneratedByTheServerAndNeverAcceptedFromTheForm(): void
    {
        $broker = $this->Brokers->newEntity([
            'partner_id' => 1,
            'name' => 'Pedro Corretor',
            'token' => 'escolhido-por-mim',
            'active' => true,
        ]);
        $this->Brokers->saveOrFail($broker);

        $this->assertNotSame('escolhido-por-mim', $broker->token);
        $this->assertMatchesRegularExpression('/^[a-z2-9]{12}$/', $broker->token);
    }

    public function testCpfIsOptionalButValidatedAndStoredAsDigits(): void
    {
        $withoutCpf = $this->Brokers->newEntity(['partner_id' => 1, 'name' => 'Sem CPF', 'cpf' => '', 'active' => true]);
        $this->assertSame([], $withoutCpf->getErrors());
        $this->assertNull($withoutCpf->cpf);

        $invalid = $this->Brokers->newEntity(['partner_id' => 1, 'name' => 'CPF Errado', 'cpf' => '111.111.111-11', 'active' => true]);
        $this->assertArrayHasKey('cpf', $invalid->getErrors());

        $valid = $this->Brokers->newEntity(['partner_id' => 1, 'name' => 'CPF Certo', 'cpf' => '390.533.447-05', 'active' => true]);
        $this->assertSame([], $valid->getErrors());
        $this->assertSame('39053344705', $valid->cpf);
    }

    public function testNameIsRequired(): void
    {
        $broker = $this->Brokers->newEntity(['partner_id' => 1, 'name' => '  ', 'active' => true]);

        $this->assertArrayHasKey('name', $broker->getErrors());
    }

    /**
     * A mesma pessoa pode atender por dois parceiros, mas não aparecer duas
     * vezes no mesmo.
     */
    public function testCpfIsUniqueOnlyWithinThePartner(): void
    {
        $partners = TableRegistry::getTableLocator()->get('Partners');
        $other = $partners->saveOrFail($partners->newEntity(['name' => 'Outro Parceiro', 'benefit_plan' => 'plenoprev', 'color' => '#123456', 'active' => true]));

        $samePartner = $this->Brokers->newEntity(['partner_id' => 1, 'name' => 'Repetido', 'cpf' => '529.982.247-25', 'active' => true]);
        $this->assertFalse($this->Brokers->save($samePartner));

        $otherPartner = $this->Brokers->newEntity(['partner_id' => $other->id, 'name' => 'João da Silva', 'cpf' => '529.982.247-25', 'active' => true]);
        $this->assertNotFalse($this->Brokers->save($otherPartner));

        // Sem CPF, não há o que colidir.
        $this->Brokers->saveOrFail($this->Brokers->newEntity(['partner_id' => 1, 'name' => 'A', 'active' => true]));
        $this->Brokers->saveOrFail($this->Brokers->newEntity(['partner_id' => 1, 'name' => 'B', 'active' => true]));
    }

    public function testAnAssociationCannotHaveBrokers(): void
    {
        $broker = $this->Brokers->newEntity(['partner_id' => 2, 'name' => 'Corretor do Sindicato', 'active' => true]);

        $this->assertFalse($this->Brokers->save($broker));
        $this->assertArrayHasKey('partner_id', $broker->getErrors());
    }

    public function testSelectableExcludesInactiveBrokersAndInactivePartners(): void
    {
        $this->assertSame([1], $this->Brokers->find('selectable')->all()->extract('id')->toList());

        $partners = TableRegistry::getTableLocator()->get('Partners');
        $partner = $partners->get(1);
        $partner->active = false;
        $partners->saveOrFail($partner);

        $this->assertSame(0, $this->Brokers->find('selectable')->count());
        $this->assertNull($this->Brokers->findSelectableByToken('joaoabc23456', 'plenoprev'));
    }

    public function testFindSelectableByToken(): void
    {
        $this->assertSame(1, $this->Brokers->findSelectableByToken('joaoabc23456', 'plenoprev')?->id);
        $this->assertNull($this->Brokers->findSelectableByToken('mariabc23456', 'plenoprev'));
        $this->assertNull($this->Brokers->findSelectableByToken('naoexiste', 'plenoprev'));
        $this->assertNull($this->Brokers->findSelectableByToken(null, 'plenoprev'));
    }

    public function testLabelIsPartnerThenBroker(): void
    {
        $broker = $this->Brokers->get(1, contain: ['Partners']);

        $this->assertSame('Corretora Parceira - João da Silva', $broker->label);
    }
}
