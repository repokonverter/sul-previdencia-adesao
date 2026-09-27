<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Partner;
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

        $validator
            ->scalar('declaration_title')
            ->allowEmptyString('declaration_title')
            ->maxLength('declaration_title', 120);

        $validator
            ->scalar('declaration_institution_name')
            ->allowEmptyString('declaration_institution_name')
            ->maxLength('declaration_institution_name', 120);

        $validator
            ->scalar('declaration_body')
            ->allowEmptyString('declaration_body');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules;
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
     * concluídas), somando todos os códigos promocionais do parceiro.
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

        $started = $adhesionsTable->find()
            ->select(['count' => $query->func()->count('*')])
            ->innerJoinWith('PromotionalCodes')
            ->where([
                'PromotionalCodes.partner_id' => $query->identifier('Partners.id'),
            ]);

        $completed = $adhesionsTable->find()
            ->select(['count' => $query->func()->count('*')])
            ->innerJoinWith('PromotionalCodes')
            ->innerJoinWith('AdhesionOtherInformations')
            ->where([
                'PromotionalCodes.partner_id' => $query->identifier('Partners.id'),
            ]);

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
