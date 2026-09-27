<?php

declare(strict_types=1);

namespace App\Services;

use App\Utility\Money;
use Cake\Datasource\EntityInterface;

/**
 * A tradução entre os nomes do formulário e as colunas da adesão.
 *
 * Existe para haver uma lista só. Retomar uma proposta exige percorrer esse
 * mapeamento na direção inversa, e escrever a segunda lista à mão criaria duas
 * de cento e poucos itens que precisariam concordar para sempre — no dia em
 * que alguém acrescentasse uma coluna e atualizasse apenas o save(), a
 * retomada perderia aquele campo em silêncio: sem erro, sem log, só um campo
 * que o proponente já tinha preenchido aparecendo vazio, para ele preencher
 * diferente e o contrato sair com outro dado.
 *
 * Fica aqui a tradução, e só ela. O que é regra — o corretor que trava na
 * primeira validação, o código promocional que não se desfaz, o nome do banco
 * derivado do número, o regime de previdência que vira várias linhas —
 * continua em [[RegistrationsController::save]], que é onde se lê junto com o
 * resto da transação.
 */
final class AdhesionFormMap
{
    public const MONEY = 'money';
    public const DECIMAL = 'decimal';
    public const CEP = 'cep';
    public const DATE = 'date';
    public const BOOLEAN = 'boolean';

    /**
     * Cada seção é uma etapa do formulário. `property` é a associação na
     * adesão; `many` marca as que são lista.
     *
     * Em `fields`, a chave é o nome no formulário e o valor descreve a coluna:
     * `column` sempre, `cast` quando o valor precisa de conversão, `default`
     * quando a ausência do campo não deve virar null (o formulário omite
     * campo vazio, e algumas colunas são notEmpty).
     */
    public const SECTIONS = [
        'initialData' => [
            'table' => 'AdhesionInitialDatas',
            'property' => null,
            'fields' => [
                'name' => ['column' => 'name', 'default' => ''],
                'email' => ['column' => 'email'],
                'phone' => ['column' => 'phone'],
            ],
        ],

        'personalData' => [
            'table' => 'AdhesionPersonalDatas',
            'property' => 'adhesion_personal_data',
            'fields' => [
                'planFor' => ['column' => 'plan_for', 'default' => ''],
                'name' => ['column' => 'name', 'default' => ''],
                'cpf' => ['column' => 'cpf', 'default' => ''],
                'birthDate' => ['column' => 'birth_date', 'cast' => self::DATE],
                'nacionality' => ['column' => 'nacionality', 'default' => ''],
                'gender' => ['column' => 'gender'],
                'maritalStatus' => ['column' => 'marital_status'],
                'numberChildren' => ['column' => 'number_children'],
                'motherName' => ['column' => 'mother_name'],
                'fatherName' => ['column' => 'father_name'],
                'nameLegalRepresentative' => ['column' => 'name_legal_representative', 'default' => ''],
                'cpfLegalRepresentative' => ['column' => 'cpf_legal_representative', 'default' => ''],
                'affiliationLegalRepresentative' => ['column' => 'affiliation_legal_representative', 'default' => ''],
            ],
        ],

        'documents' => [
            'table' => 'AdhesionDocuments',
            'property' => 'adhesion_document',
            'fields' => [
                'documentType' => ['column' => 'type'],
                // type_other não está aqui porque não existe campo para ele no
                // formulário -- havia só no save(), gravando null desde sempre.
                // Fora do mapa, o patch não o toca, e um valor posto pelo admin
                // sobrevive a um novo envio da etapa.
                'documentNumber' => ['column' => 'document_number'],
                'issueDate' => ['column' => 'issue_date', 'cast' => self::DATE],
                'issuer' => ['column' => 'issuer'],
                'placeBirth' => ['column' => 'place_birth'],
            ],
        ],

        'plans' => [
            'table' => 'AdhesionPlans',
            'property' => 'adhesion_plan',
            'fields' => [
                'benefitEntryAge' => ['column' => 'benefit_entry_age'],
                'monthly_retirement_contribution' => ['column' => 'monthly_retirement_contribution', 'cast' => self::MONEY],
                'monthly_survivors_pension_contribution' => ['column' => 'monthly_survivors_pension_contribution', 'cast' => self::MONEY],
                'survivors_pension_insured_capital' => ['column' => 'survivors_pension_insured_capital', 'cast' => self::MONEY],
                'monthly_disability_retirement_contribution' => ['column' => 'monthly_disability_retirement_contribution', 'cast' => self::MONEY],
                'disability_retirement_insured_capital' => ['column' => 'disability_retirement_insured_capital', 'cast' => self::MONEY],
            ],
        ],

        'dependents' => [
            'table' => 'AdhesionDependents',
            'property' => 'adhesion_dependents',
            'many' => true,
            'fields' => [
                'name' => ['column' => 'name', 'default' => ''],
                'cpf' => ['column' => 'cpf'],
                'birth_date' => ['column' => 'birth_date', 'cast' => self::DATE],
                'kinship' => ['column' => 'kinship'],
                'participation' => ['column' => 'participation', 'cast' => self::DECIMAL],
            ],
        ],

        'addresses' => [
            'table' => 'AdhesionAddresses',
            'property' => 'adhesion_address',
            'fields' => [
                'cep' => ['column' => 'cep', 'cast' => self::CEP, 'default' => ''],
                'address' => ['column' => 'address', 'default' => ''],
                'number' => ['column' => 'number', 'default' => ''],
                'complement' => ['column' => 'complement', 'default' => ''],
                'neighborhood' => ['column' => 'neighborhood', 'default' => ''],
                'city' => ['column' => 'city', 'default' => ''],
                'state' => ['column' => 'state', 'default' => ''],
            ],
        ],

        'otherInformations' => [
            'table' => 'AdhesionOtherInformations',
            'property' => 'adhesion_other_information',
            'fields' => [
                'mainOccupationDescription' => ['column' => 'main_occupation_description', 'default' => ''],
                'mainOccupationCode' => ['column' => 'main_occupation_code', 'default' => ''],
                'category' => ['column' => 'category', 'default' => ''],
                'brazilianResident' => ['column' => 'brazilian_resident', 'cast' => self::BOOLEAN, 'default' => false],
                'brazilianResidentObs' => ['column' => 'brazilian_resident_obs', 'default' => ''],
                'politicallyExposed' => ['column' => 'politically_exposed', 'cast' => self::BOOLEAN, 'default' => false],
                'politicallyExposedObs' => ['column' => 'politically_exposed_obs', 'default' => ''],
                'obligationOtherCountries' => ['column' => 'obligation_other_countries', 'cast' => self::BOOLEAN, 'default' => false],
                'obligationOtherCountriesObs' => ['column' => 'obligation_other_countries_obs', 'default' => ''],
                'company' => ['column' => 'company', 'default' => ''],
                'monthlyIncome' => ['column' => 'monthly_income', 'cast' => self::MONEY],
            ],
        ],

        'proponentStatement' => [
            'table' => 'AdhesionProponentStatements',
            'property' => 'adhesion_proponent_statement',
            'fields' => [
                'healthProblem' => ['column' => 'health_problem', 'cast' => self::BOOLEAN, 'default' => false],
                'healthProblemObs' => ['column' => 'health_problem_obs', 'default' => ''],
                'heartDisease' => ['column' => 'heart_disease', 'cast' => self::BOOLEAN, 'default' => false],
                'heartDiseaseObs' => ['column' => 'heart_disease_obs', 'default' => ''],
                'sufferedOrganDefects' => ['column' => 'suffered_organ_defects', 'cast' => self::BOOLEAN, 'default' => false],
                'sufferedOrganDefectsObs' => ['column' => 'suffered_organ_defects_obs', 'default' => ''],
                'surgery' => ['column' => 'surgery', 'cast' => self::BOOLEAN, 'default' => false],
                'surgeryObs' => ['column' => 'surgery_obs', 'default' => ''],
                'away' => ['column' => 'away', 'cast' => self::BOOLEAN, 'default' => false],
                'awayObs' => ['column' => 'away_obs', 'default' => ''],
                'practicesParachuting' => ['column' => 'practices_parachuting', 'cast' => self::BOOLEAN, 'default' => false],
                'practicesParachutingObs' => ['column' => 'practices_parachuting_obs', 'default' => ''],
                'smoker' => ['column' => 'smoker', 'cast' => self::BOOLEAN, 'default' => false],
                'smokerType' => ['column' => 'smoker_type', 'default' => ''],
                'smokerTypeObs' => ['column' => 'smoker_type_obs', 'default' => ''],
                'smokerQty' => ['column' => 'smoker_qty', 'default' => ''],
                'weight' => ['column' => 'weight', 'cast' => self::DECIMAL],
                'height' => ['column' => 'height', 'cast' => self::DECIMAL],
                'gripe' => ['column' => 'gripe', 'cast' => self::BOOLEAN, 'default' => false],
                'gripeObs' => ['column' => 'gripe_obs', 'default' => ''],
                'covid' => ['column' => 'covid', 'cast' => self::BOOLEAN, 'default' => false],
                'covidObs' => ['column' => 'covid_obs', 'default' => ''],
                'covidSequelae' => ['column' => 'covid_sequelae', 'cast' => self::BOOLEAN, 'default' => false],
                'covidSequelaeObs' => ['column' => 'covid_sequelae_obs', 'default' => ''],
            ],
        ],

        'paymentDetail' => [
            'table' => 'AdhesionPaymentDetails',
            'property' => 'adhesion_payment_detail',
            'fields' => [
                'due_date' => ['column' => 'due_date', 'default' => ''],
                'total_contribution' => ['column' => 'total_contribution', 'cast' => self::MONEY],
                'payment_type' => ['column' => 'payment_type', 'default' => ''],
                'account_holder_name' => ['column' => 'account_holder_name'],
                'account_holder_cpf' => ['column' => 'account_holder_cpf'],
                'bank_number' => ['column' => 'bank_number'],
                'branch_number' => ['column' => 'branch_number'],
                'account_number' => ['column' => 'account_number'],
            ],
        ],
    ];

    /**
     * Do formulário para as colunas.
     *
     * @param array<string, mixed> $payload a seção como o formulário a enviou
     * @return array<string, mixed>
     */
    public static function toColumns(string $section, array $payload): array
    {
        $columns = [];

        foreach (self::SECTIONS[$section]['fields'] as $field => $spec) {
            if (!array_key_exists($field, $payload) && !array_key_exists('default', $spec)) {
                $columns[$spec['column']] = null;

                continue;
            }

            $columns[$spec['column']] = array_key_exists($field, $payload)
                ? self::castIn($payload[$field], $spec['cast'] ?? null)
                : $spec['default'];
        }

        return $columns;
    }

    /**
     * Das colunas para o formulário, para repopular uma proposta retomada.
     *
     * @return array<string, mixed> vazio quando a seção ainda não foi preenchida
     */
    public static function toForm(string $section, ?EntityInterface $entity): array
    {
        if ($entity === null) {
            return [];
        }

        $payload = [];

        foreach (self::SECTIONS[$section]['fields'] as $field => $spec) {
            $payload[$field] = self::castOut($entity->get($spec['column']), $spec['cast'] ?? null);
        }

        return $payload;
    }

    /**
     * A adesão inteira no formato que o formulário entende, para repopular
     * uma proposta retomada.
     *
     * O regime de previdência não está em SECTIONS porque não é tradução
     * campo-a-campo: uma etapa vira várias linhas, uma por tipo marcado, com
     * o nome, CPF e parentesco repetidos em todas. Aqui ela volta a ser uma
     * etapa só.
     *
     * @return array<string, mixed>
     */
    public static function toFormPayload(EntityInterface $adhesion): array
    {
        $payload = [];

        foreach (self::SECTIONS as $section => $definition) {
            $property = $definition['property'];

            if ($property === null) {
                $payload[$section] = self::toForm($section, $adhesion);

                continue;
            }

            $related = $adhesion->get($property);

            $payload[$section] = empty($definition['many'])
                ? self::toForm($section, $related)
                : array_values(array_map(
                    fn(EntityInterface $item): array => self::toForm($section, $item),
                    (array)$related
                ));
        }

        $schemes = (array)$adhesion->get('adhesion_pension_schemes');
        $first = $schemes[0] ?? null;

        $payload['pensionScheme'] = $first === null ? [] : [
            'pensionSchemeType' => array_values(array_map(
                fn(EntityInterface $scheme): string => (string)$scheme->get('pension_scheme'),
                $schemes
            )),
            'name' => $first->get('name'),
            'cpf' => $first->get('cpf'),
            'kinship' => $first->get('kinship'),
        ];

        return $payload;
    }

    private static function castIn(mixed $value, ?string $cast): mixed
    {
        return match ($cast) {
            self::MONEY => Money::parse($value),
            // Peso, altura e participação vêm só com vírgula decimal, sem
            // milhar, então trocar a vírgula basta e é seguro para ambas as
            // grafias.
            self::DECIMAL => $value === null || $value === '' ? null : str_replace(',', '.', (string)$value),
            self::CEP => str_replace(['.', '-'], '', (string)$value),
            default => $value,
        };
    }

    private static function castOut(mixed $value, ?string $cast): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($cast) {
            // Devolvido em pt-BR, que é a grafia que o próprio recálculo
            // produz. As duas são aceitas de volta (ver Money::parse), mas é
            // esta que a pessoa reconhece na tela.
            self::MONEY => number_format((float)$value, 2, ',', '.'),
            self::DECIMAL => str_replace('.', ',', (string)$value),
            // Checa o método, e não DateTimeInterface: Cake\I18n\Date envolve
            // ChronosDate, que não implementa a interface. Cair no cast para
            // string devolveria "10/03/1985", que um <input type="date"> recusa
            // -- e o campo apareceria vazio na proposta retomada, como se o
            // proponente nunca tivesse informado a data.
            self::DATE => is_object($value) && method_exists($value, 'format')
                ? $value->format('Y-m-d')
                : (string)$value,
            self::BOOLEAN => $value ? '1' : '0',
            default => $value,
        };
    }
}
