<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Services\BenefitPlans;
use App\Services\PixPaymentService;
use App\Test\TestSuite\SecondBenefitPlanTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

class BenefitPlansTest extends TestCase
{
    use SecondBenefitPlanTrait;

    protected array $fixtures = [
        'app.PlanParameters',
    ];

    public function testTheHostPicksThePlan(): void
    {
        $this->configureSecondBenefitPlan();

        $this->assertSame('plenoprev', BenefitPlans::forHost('plenoprev.konverter.com.br')?->slug);
        $this->assertSame('maispremium', BenefitPlans::forHost('MaisPremium.Konverter.com.br')?->slug);
        $this->assertSame('maispremium', BenefitPlans::forHost('www.maispremium.konverter.com.br:443')?->slug);
    }

    public function testAnUnknownHostFallsBackOnlyToDefaultPlan(): void
    {
        $this->assertSame('plenoprev', BenefitPlans::forHost('localhost')?->slug);

        Configure::write('App.defaultBenefitPlan', null);

        try {
            $this->assertNull(BenefitPlans::forHost('konverter.com.br'));
            $this->assertNull(BenefitPlans::forHost('143.95.173.13'));
        } finally {
            Configure::write('App.defaultBenefitPlan', 'plenoprev');
        }
    }

    public function testThePrimaryPlanIsTheFirstConfigured(): void
    {
        $this->configureSecondBenefitPlan();

        $this->assertSame('plenoprev', BenefitPlans::primary()->slug);
        $this->assertSame(['plenoprev' => 'PlenoPrev', 'maispremium' => 'MaisPremium'], BenefitPlans::options());
    }

    public function testAnUnknownSlugFailsLoudly(): void
    {
        $this->assertFalse(BenefitPlans::exists('outroplano'));
        $this->assertFalse(BenefitPlans::exists(null));

        $this->expectException(InvalidArgumentException::class);
        BenefitPlans::get('outroplano');
    }

    public function testUrlsGoToThePlansOwnHost(): void
    {
        $this->configureSecondBenefitPlan();

        $this->assertSame(
            'https://maispremium.konverter.com.br/pagamento/abc',
            BenefitPlans::get('maispremium')->url('/pagamento/abc')
        );
    }

    /**
     * Cobranças já criadas no Sicoob têm txid com o prefixo antigo, fixo: o
     * do PlenoPrev tem que continuar gerando exatamente o mesmo.
     */
    public function testThePlenoPrevTxidDidNotChange(): void
    {
        $base = 'PLENOPREV' . '000123' . '02';

        $this->assertSame(
            $base . strtoupper(substr(md5($base), 0, 13)),
            PixPaymentService::generateTxid(BenefitPlans::get('plenoprev')->txidPrefix, 123, 2)
        );
    }

    public function testTheTxidFitsTheBacenLimitWithTheLongestPrefix(): void
    {
        $this->configureSecondBenefitPlan();

        $txid = PixPaymentService::generateTxid(BenefitPlans::get('maispremium')->txidPrefix, 999999, 99);

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{26,35}$/', $txid);
        $this->assertStringStartsWith('MAISPREMIUM', $txid);
    }
}
