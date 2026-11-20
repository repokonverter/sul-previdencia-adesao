<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AdhesionInitialDatasTable;
use App\Test\TestSuite\SecondBenefitPlanTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * As regras de plano de benefícios da adesão: nasce num plano conhecido,
 * não troca de plano, e só recebe código, corretor e vínculo do mesmo plano.
 */
class AdhesionInitialDatasTableTest extends TestCase
{
    use SecondBenefitPlanTrait;

    protected array $fixtures = [
        'app.PlanParameters',
        'app.Partners',
        'app.PromotionalCodes',
        'app.Brokers',
    ];

    private AdhesionInitialDatasTable $Adhesions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSecondBenefitPlan();
        $this->Adhesions = TableRegistry::getTableLocator()->get('AdhesionInitialDatas');
    }

    private function newAdhesion(array $data): \App\Model\Entity\AdhesionInitialData
    {
        return $this->Adhesions->newEntity($data + [
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
        ]);
    }

    public function testAnAdhesionNeedsAKnownPlan(): void
    {
        $this->assertFalse($this->Adhesions->save($this->newAdhesion([])));
        $this->assertFalse($this->Adhesions->save($this->newAdhesion(['benefit_plan' => 'outroplano'])));
        $this->assertNotFalse($this->Adhesions->save($this->newAdhesion(['benefit_plan' => self::SECOND_PLAN])));
    }

    public function testThePlanDoesNotChangeAfterCreation(): void
    {
        $adhesion = $this->Adhesions->saveOrFail($this->newAdhesion(['benefit_plan' => 'plenoprev']));

        $adhesion = $this->Adhesions->patchEntity($adhesion, ['benefit_plan' => self::SECOND_PLAN]);

        $this->assertFalse($this->Adhesions->save($adhesion));
        $this->assertArrayHasKey('benefitPlanUnchanged', $adhesion->getError('benefit_plan'));
        $this->assertSame('plenoprev', $this->Adhesions->get($adhesion->id)->benefit_plan);
    }

    /**
     * Os parceiros das fixtures são do PlenoPrev.
     */
    public function testAttributionMustComeFromTheSamePlan(): void
    {
        $fromOtherPlan = [
            'broker_id' => 1,
            'promotional_code_id' => 1,
            'association_partner_id' => 2,
        ];

        foreach ($fromOtherPlan as $field => $id) {
            $adhesion = $this->newAdhesion(['benefit_plan' => self::SECOND_PLAN, $field => $id]);

            $this->assertFalse($this->Adhesions->save($adhesion), $field);
            $this->assertArrayHasKey('benefitPlan', $adhesion->getError($field), $field);

            $adhesion = $this->newAdhesion(['benefit_plan' => 'plenoprev', $field => $id]);
            $this->assertNotFalse($this->Adhesions->save($adhesion), $field);
        }
    }
}
