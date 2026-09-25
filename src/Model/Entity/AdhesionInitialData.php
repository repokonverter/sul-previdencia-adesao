<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionInitialData extends Entity
{
    protected array $_accessible = [
        'id' => true,
        'storage_uuid' => true,
        'name' => true,
        'email' => true,
        'phone' => true,
        'promotional_code_id' => true,
        'promotional_code' => true,
        'association_partner_id' => true,
        'association_snapshot' => true,
        'broker_id' => true,
        'broker_name' => true,
        'broker_code' => true,
        'resume_token' => true,
        'resume_token_expires_at' => true,
        'resume_step' => true,
        'created' => true,
        'modified' => true,
        'adhesion_personal_data' => true,
        'adhesion_plans' => true,
        'adhesion_addresses' => true,
        'adhesion_other_information' => true,
        'adhesion_documents' => true,
        'adhesion_dependents' => true,
        'adhesion_pension_schemes' => true,
        'adhesion_payment_details' => true,
        'adhesion_proponent_statements' => true,
        'clicksign_datas' => true,
        'pix_transaction' => true,
        'promotional_code_entity' => true,
        'association_partner' => true,
        'broker' => true,
    ];

    /**
     * association_snapshot é gravado como JSON (title, institutionName, body)
     * pelo RegistrationsController, no momento em que o vínculo é validado
     * pela primeira vez. Decodificado aqui para que a geração do PDF e as
     * views não precisem repetir o json_decode.
     *
     * @return array{title: string, institutionName: string, body: string|null}|null
     */
    protected function _getAssociationTexts(): ?array
    {
        if ($this->association_partner_id === null || $this->association_snapshot === null) {
            return null;
        }

        $decoded = json_decode((string)$this->association_snapshot, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * O link de retomada vale? Expirado e revogado são estados diferentes
     * para quem abre a página: um explica que o prazo acabou, o outro é um
     * link que nunca existiu.
     */
    public function resumeTokenHasExpired(): bool
    {
        return $this->resume_token_expires_at !== null
            && $this->resume_token_expires_at->isPast();
    }
}

