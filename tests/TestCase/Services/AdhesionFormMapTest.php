<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Services\AdhesionFormMap;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O teste que faz o mapeamento valer a pena.
 *
 * Retomar uma proposta percorre o mapeamento na direção inversa. Se alguém
 * acrescentar uma coluna e atualizar só um dos lados, a retomada passa a
 * perder aquele campo em silêncio — sem erro, sem log, só um campo que o
 * proponente já tinha preenchido aparecendo vazio. Estes testes transformam
 * essa divergência de silenciosa em barulhenta.
 */
class AdhesionFormMapTest extends TestCase
{
    protected array $fixtures = [
        'app.PlanParameters',
    ];

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    /**
     * Um valor plausível para cada campo: pelo cast quando há um, senão pelo
     * tipo da coluna, truncado ao tamanho que ela aceita — gender é
     * varchar(1), e um valor genérico não caberia.
     */
    private function sampleFor(array $spec, \Cake\Database\Schema\TableSchemaInterface $schema): string
    {
        $cast = $spec['cast'] ?? null;

        if ($cast !== null) {
            return match ($cast) {
                AdhesionFormMap::MONEY => '1.234,56',
                AdhesionFormMap::DECIMAL => '1,80',
                AdhesionFormMap::DATE => '1985-03-10',
                AdhesionFormMap::BOOLEAN => '1',
                AdhesionFormMap::CEP => '88000-000',
            };
        }

        $column = $schema->getColumn($spec['column']);

        return match (true) {
            str_contains((string)$column['type'], 'integer') => '7',
            (string)$column['type'] === 'decimal' => '12.34',
            (string)$column['type'] === 'boolean' => '1',
            in_array((string)$column['type'], ['date', 'datetime'], true) => '1985-03-10',
            default => substr('valor', 0, $column['length'] ?? 5) ?: 'v',
        };
    }

    private function samplePayload(string $section): array
    {
        $schema = $this->table(AdhesionFormMap::SECTIONS[$section]['table'])->getSchema();
        $payload = [];

        foreach (AdhesionFormMap::SECTIONS[$section]['fields'] as $field => $spec) {
            $payload[$field] = $this->sampleFor($spec, $schema);
        }

        return $payload;
    }

    private function createAdhesion(): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        return (int)$adhesion->id;
    }

    /**
     * A propriedade que importa: o que entra pelo formulário sobrevive à ida
     * ao banco e à volta. A comparação é entre colunas, e não entre os
     * payloads crus, porque algumas normalizações são desejadas — o CEP
     * "88000-000" é gravado sem pontuação e volta assim.
     */
    public function testEverySectionSurvivesTheRoundTrip(): void
    {
        $adhesionId = $this->createAdhesion();

        foreach (AdhesionFormMap::SECTIONS as $section => $definition) {
            if ($section === 'initialData') {
                continue; // a raiz já existe; coberta pelo save()
            }

            $payload = $this->samplePayload($section);
            $sent = AdhesionFormMap::toColumns($section, $payload);

            $table = $this->table($definition['table']);
            $entity = $table->newEntity($sent + ['adhesion_initial_data_id' => $adhesionId]);
            $table->saveOrFail($entity);

            $reloaded = $table->get($entity->id);
            $returned = AdhesionFormMap::toColumns($section, AdhesionFormMap::toForm($section, $reloaded));

            foreach ($sent as $column => $value) {
                $this->assertEquals(
                    $value,
                    $returned[$column],
                    "$section.$column não sobreviveu à ida e volta"
                );
            }
        }
    }

    /**
     * Uma coluna com nome errado no mapeamento seria ignorada pelo patch: o
     * campo simplesmente não gravaria, sem reclamação nenhuma.
     */
    public function testEveryMappedColumnExistsInItsTable(): void
    {
        foreach (AdhesionFormMap::SECTIONS as $section => $definition) {
            $columns = $this->table($definition['table'])->getSchema()->columns();

            foreach ($definition['fields'] as $field => $spec) {
                $this->assertContains(
                    $spec['column'],
                    $columns,
                    "$section.$field aponta para uma coluna que não existe em {$definition['table']}"
                );
            }
        }
    }

    /**
     * O formulário omite campo vazio. Sem o default declarado, a ausência
     * viraria null numa coluna que não aceita null.
     */
    public function testAbsentFieldsFallBackToTheirDeclaredDefault(): void
    {
        $columns = AdhesionFormMap::toColumns('addresses', []);

        $this->assertSame('', $columns['city']);
        $this->assertSame('', $columns['cep']);
    }

    public function testAbsentFieldsWithoutADefaultBecomeNull(): void
    {
        $columns = AdhesionFormMap::toColumns('personalData', []);

        $this->assertNull($columns['gender']);
        $this->assertNull($columns['birth_date']);
    }

    /**
     * A grafia devolvida é a que o recálculo produz, e é a que a pessoa
     * reconhece na tela.
     */
    public function testMoneyComesBackInBrazilianSpelling(): void
    {
        $plans = $this->table('AdhesionPlans');
        $plan = $plans->newEntity([
            'adhesion_initial_data_id' => $this->createAdhesion(),
            'monthly_survivors_pension_contribution' => '1234.56',
        ]);
        $plans->saveOrFail($plan);

        $form = AdhesionFormMap::toForm('plans', $plans->get($plan->id));

        $this->assertSame('1.234,56', $form['monthly_survivors_pension_contribution']);
    }

    public function testAnUnfilledSectionComesBackEmpty(): void
    {
        $this->assertSame([], AdhesionFormMap::toForm('personalData', null));
    }
}
