<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\PdfGeneratorComponent;
use Cake\Controller\Controller;
use Cake\Controller\ComponentRegistry;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O que está escrito na proposta que alguém vai assinar.
 *
 * Foca na Declaração Pessoal de Saúde: o template avisa, no próprio título,
 * que ela nunca deve ser assinada em branco, e imprimir "Não" onde ninguém
 * respondeu é pior que em branco, porque parece legítimo.
 */
class PdfGeneratorComponentTest extends TestCase
{
    /**
     * O questionário de saúde, isolado do resto do documento.
     *
     * O título "DECLARAÇÕES DO PROPONENTE" aparece três vezes na proposta: o
     * questionário, o aviso de "não se aplica" quando não há risco, e uma
     * declaração jurídica sobre estatuto e veracidade que nada tem a ver com
     * saúde e existe sempre. O subtítulo só existe no questionário.
     */
    private const HEALTH_MARKER = 'Declaração Pessoal de Saúde';

    protected array $fixtures = [
        'app.PlanParameters',
    ];

    private PdfGeneratorComponent $pdf;

    protected function setUp(): void
    {
        parent::setUp();

        $controller = new Controller(new ServerRequest());
        $this->pdf = new PdfGeneratorComponent(new ComponentRegistry($controller));
    }

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    /**
     * Adesão completa o bastante para a proposta renderizar. A declaração de
     * saúde é opcional de propósito: é justamente a ausência dela que este
     * arquivo cobre.
     */
    private function createAdhesion(bool $withStatement, bool $hasRisks = true): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);
        $id = (int)$adhesion->id;

        $rows = [
            'AdhesionPersonalDatas' => [
                'plan_for' => 'Titular',
                'name' => 'Fulano de Tal',
                'cpf' => '123.456.789-09',
                'birth_date' => '1985-03-10',
                'nacionality' => 'Brasileira',
                'gender' => 'M',
                'marital_status' => 'Solteiro',
            ],
            'AdhesionDocuments' => [
                'type' => 'RG',
                'document_number' => '1234567',
                'issue_date' => '2010-01-01',
                'issuer' => 'SSP',
                'place_birth' => 'Florianópolis',
            ],
            'AdhesionAddresses' => [
                'cep' => '88000000',
                'address' => 'Rua Vidal Ramos',
                'number' => '31',
                'neighborhood' => 'Centro',
                'city' => 'Florianópolis',
                'state' => 'SC',
            ],
            'AdhesionOtherInformations' => [
                'main_occupation_description' => 'Profissional liberal',
                'main_occupation_code' => '999999',
                'category' => 'Autônomo',
                'monthly_income' => '5000.00',
            ],
            'AdhesionPlans' => [
                'benefit_entry_age' => 65,
                'monthly_retirement_contribution' => '740.00',
                'monthly_survivors_pension_contribution' => $hasRisks ? '160.00' : '0.00',
                'survivors_pension_insured_capital' => $hasRisks ? '1000.00' : '0.00',
                'monthly_disability_retirement_contribution' => $hasRisks ? '100.00' : '0.00',
                'disability_retirement_insured_capital' => $hasRisks ? '1000.00' : '0.00',
                'has_survivors_pension' => $hasRisks,
                'has_disability_retirement' => $hasRisks,
            ],
            'AdhesionPaymentDetails' => [
                'total_contribution' => '1000.00',
                'payment_type' => 'Boleto bancário',
            ],
        ];

        foreach ($rows as $table => $data) {
            $this->table($table)->saveOrFail(
                $this->table($table)->newEntity($data + ['adhesion_initial_data_id' => $id])
            );
        }

        if ($withStatement) {
            $this->table('AdhesionProponentStatements')->saveOrFail(
                $this->table('AdhesionProponentStatements')->newEntity([
                    'adhesion_initial_data_id' => $id,
                    'health_problem' => false,
                    'heart_disease' => false,
                    'suffered_organ_defects' => false,
                    'surgery' => false,
                    'away' => false,
                    'practices_parachuting' => false,
                    'smoker' => false,
                    'weight' => '80',
                    'height' => '1.80',
                    'gripe' => false,
                    'covid' => false,
                    'covid_sequelae' => false,
                ])
            );
        }

        return $id;
    }

    private function healthQuestionnaire(string $html): ?string
    {
        $start = strpos($html, self::HEALTH_MARKER);

        if ($start === false)
            return null;

        return substr($html, $start, strpos($html, '</table>', $start) - $start);
    }

    /**
     * O cenário que o admin destrava ao poder religar um risco: a adesão
     * nunca teve declaração de saúde, e o risco voltou a existir.
     */
    public function testHealthAnswersAreNotInventedWhenNobodyDeclared(): void
    {
        $questionnaire = $this->healthQuestionnaire(
            $this->pdf->applicationFormHtml($this->createAdhesion(withStatement: false))
        );

        $this->assertNotNull($questionnaire, 'com risco contratado o questionário tem de aparecer');
        $this->assertStringContainsString('NÃO DECLARADO', $questionnaire);

        // Nenhuma das onze perguntas pode sair respondida por conta própria.
        $this->assertStringNotContainsString('<td>Não</td>', $questionnaire);
    }

    public function testAnsweredDeclarationStillPrintsTheAnswers(): void
    {
        $questionnaire = $this->healthQuestionnaire(
            $this->pdf->applicationFormHtml($this->createAdhesion(withStatement: true))
        );

        $this->assertNotNull($questionnaire);
        $this->assertStringContainsString('<td>Não</td>', $questionnaire);
        $this->assertStringNotContainsString('NÃO DECLARADO', $questionnaire);
        $this->assertStringContainsString('Kg e 1,80 m', $questionnaire);
    }

    /**
     * Sem risco contratado a seção inteira some, e por isso a ausência de
     * declaração não incomoda ninguém — é o acoplamento que o admin quebra ao
     * poder religar um risco.
     */
    public function testTheSectionDisappearsWithoutAnyRisk(): void
    {
        $html = $this->pdf->applicationFormHtml($this->createAdhesion(withStatement: false, hasRisks: false));

        $this->assertNull($this->healthQuestionnaire($html));
        $this->assertStringContainsString('Não se aplica', $html);
        $this->assertStringContainsString('NÃO CONTRATADO', $html);
    }
}
