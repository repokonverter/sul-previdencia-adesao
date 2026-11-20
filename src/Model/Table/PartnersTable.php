<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Partner;
use App\Services\BenefitPlans;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Psr\Http\Message\UploadedFileInterface;

class PartnersTable extends AppTable
{
    public const LOGO_MAX_BYTES = 1048576; // 1 MB

    public const LOGO_ALLOWED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('partners');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('PromotionalCodes', [
            'foreignKey' => 'partner_id',
        ]);

        $this->hasMany('Brokers', [
            'foreignKey' => 'partner_id',
        ]);

        // Adesões vinculadas a este parceiro como vínculo associativo, à
        // parte de qualquer relação por código promocional (ver
        // association_partner_id em AdhesionInitialDatasTable).
        $this->hasMany('AdhesionInitialDatas', [
            'foreignKey' => 'association_partner_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name', 'Informe o nome do parceiro.')
            ->maxLength('name', 120);

        $validator
            ->scalar('color')
            ->requirePresence('color', 'create')
            ->notEmptyString('color', 'Informe a cor do parceiro.')
            ->add('color', 'format', [
                'rule' => fn($value) => (bool)preg_match('/^#[0-9A-Fa-f]{6}$/', (string)$value),
                'message' => 'Informe uma cor no formato #RRGGBB.',
            ]);

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        $validator
            ->boolean('is_association')
            ->allowEmptyString('is_association');

        // Só exigidos para vínculo associativo (is_association = true): a
        // Declaração de Vínculo Associativo depende diretamente dos dois, e
        // um parceiro comum nem mostra estes campos no formulário.
        $requiredForAssociation = fn($context) => !empty($context['data']['is_association']);

        $validator
            ->scalar('company_name')
            ->maxLength('company_name', 150)
            ->requirePresence('company_name', $requiredForAssociation)
            ->notEmptyString('company_name', 'Informe o nome da empresa.', $requiredForAssociation);

        $validator
            ->scalar('company_cnpj')
            ->maxLength('company_cnpj', 18)
            ->requirePresence('company_cnpj', $requiredForAssociation)
            ->notEmptyString('company_cnpj', 'Informe o CNPJ da empresa.', $requiredForAssociation)
            ->add('company_cnpj', 'format', [
                // Aceita o CNPJ numérico atual e o alfanumérico que a
                // Receita Federal está introduzindo -- os dois dígitos
                // verificadores finais continuam sempre numéricos.
                'rule' => fn($value) => (bool)preg_match(
                    '/^\d{2}\.[0-9A-Za-z]{3}\.[0-9A-Za-z]{3}\/[0-9A-Za-z]{4}-\d{2}$/',
                    (string)$value
                ),
                'message' => 'Informe um CNPJ válido, no formato 00.000.000/0000-00.',
            ]);

        $validator
            ->scalar('institution_number')
            ->maxLength('institution_number', 20)
            ->requirePresence('institution_number', $requiredForAssociation)
            ->notEmptyString('institution_number', 'Informe o Nº do Instituidor.', $requiredForAssociation)
            ->add('institution_number', 'digits', [
                'rule' => fn($value) => $value === null || $value === '' || (bool)preg_match('/^\d+$/', (string)$value),
                'message' => 'O Nº do Instituidor deve conter apenas dígitos.',
            ]);

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->addCreate(
            fn(EntityInterface $partner): bool => BenefitPlans::exists($partner->get('benefit_plan')),
            'knownBenefitPlan',
            ['errorField' => 'benefit_plan', 'message' => 'Selecione um plano válido.']
        );

        // Links de divulgação e adesões já atribuídas apontam para o plano;
        // parceiro que atua nos dois é cadastrado duas vezes.
        $rules->addUpdate(
            fn(EntityInterface $partner): bool => !$partner->isDirty('benefit_plan'),
            'benefitPlanUnchanged',
            ['errorField' => 'benefit_plan', 'message' => 'O plano de um parceiro não muda depois de cadastrado.']
        );

        return $rules;
    }

    /**
     * Só os parceiros (e vínculos) de um plano de benefícios. Sem plano, não
     * filtra: é o "Todos" das listagens do admin.
     */
    public function findByBenefitPlan(SelectQuery $query, ?string $benefitPlan): SelectQuery
    {
        return $benefitPlan === null ? $query : $query->where(['Partners.benefit_plan' => $benefitPlan]);
    }

    /**
     * Restringe a busca aos vínculos associativos (is_association = true) ou
     * aos parceiros comuns (false). Os dois conjuntos são disjuntos: usado
     * pelas telas "Parceiros" e "Vínculos associativos" no admin, que
     * compartilham o mesmo controller e os mesmos templates.
     */
    public function findByAssociationScope(SelectQuery $query, bool $isAssociation): SelectQuery
    {
        return $query->where(['Partners.is_association' => $isAssociation]);
    }

    /**
     * Acrescenta as contagens de códigos ativos e de adesões (iniciadas e
     * concluídas) atribuídas ao parceiro, por código promocional ou por
     * corretor. Uma adesão com código e corretor do mesmo parceiro conta uma
     * vez só.
     */
    public function findWithAdhesionCounts(SelectQuery $query): SelectQuery
    {
        $activeCodes = $this->PromotionalCodes->find()
            ->select(['count' => $query->func()->count('*')])
            ->where([
                'PromotionalCodes.partner_id' => $query->identifier('Partners.id'),
                'PromotionalCodes.active' => true,
            ]);

        $adhesionsTable = $this->PromotionalCodes->AdhesionInitialDatas;

        $attributedToPartner = [
            'OR' => [
                'PromotionalCodes.partner_id' => $query->identifier('Partners.id'),
                'Brokers.partner_id' => $query->identifier('Partners.id'),
            ],
        ];

        // LEFT nos dois: a adesão pode ter só um deles. Os dois são
        // belongsTo, então o join não duplica linha e o COUNT(*) não infla.
        $started = $adhesionsTable->find()
            ->select(['count' => $query->func()->count('*')])
            ->leftJoinWith('PromotionalCodes')
            ->leftJoinWith('Brokers')
            ->where($attributedToPartner);

        $completed = $adhesionsTable->find()
            ->select(['count' => $query->func()->count('*')])
            ->leftJoinWith('PromotionalCodes')
            ->leftJoinWith('Brokers')
            ->innerJoinWith('AdhesionOtherInformations')
            ->where($attributedToPartner);

        return $query->selectAlso([
            'active_codes' => $activeCodes,
            'adhesions_started' => $started,
            'adhesions_completed' => $completed,
        ]);
    }

    /**
     * Valida e aplica um arquivo de logo enviado pelo admin.
     *
     * O tipo é determinado pelo conteúdo real do arquivo (finfo), nunca pela
     * extensão ou pelo Content-Type informado pelo navegador.
     *
     * @return string|null Mensagem de erro, ou null em caso de sucesso.
     */
    public function applyLogoUpload(Partner $partner, UploadedFileInterface $file): ?string
    {
        if ($file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return 'Não foi possível ler o arquivo enviado. Tente novamente.';
        }

        if ($file->getSize() !== null && $file->getSize() > self::LOGO_MAX_BYTES) {
            return 'A logo deve ter no máximo 1 MB.';
        }

        $stream = $file->getStream();
        $stream->rewind();
        $contents = $stream->getContents();

        if ($contents === '') {
            return 'O arquivo enviado está vazio.';
        }

        if (strlen($contents) > self::LOGO_MAX_BYTES) {
            return 'A logo deve ter no máximo 1 MB.';
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($contents);

        if (!in_array($mimeType, self::LOGO_ALLOWED_MIME_TYPES, true)) {
            return 'Formato inválido. Envie a logo em PNG, JPEG ou WEBP.';
        }

        $partner->set('logo_data', $contents);
        $partner->set('logo_mime_type', $mimeType);
        $partner->set('logo_filename', $file->getClientFilename());

        return null;
    }

    public function clearLogo(Partner $partner): void
    {
        $partner->set('logo_data', null);
        $partner->set('logo_mime_type', null);
        $partner->set('logo_filename', null);
    }
}
