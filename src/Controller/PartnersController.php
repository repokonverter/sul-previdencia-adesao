<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use App\Model\Table\PartnersTable;
use Cake\Http\Exception\NotFoundException;

/**
 * Endpoint público usado para servir a logo do parceiro no formulário de
 * adesão e na página de pagamento.
 */
class PartnersController extends AppController
{
    protected PartnersTable $Partners;

    public function initialize(): void
    {
        parent::initialize();

        $this->Partners = $this->fetchTable('Partners');
    }

    /**
     * Serve os bytes da logo guardados no banco.
     */
    public function logo(?string $id = null)
    {
        $this->request->allowMethod(['get']);

        $partner = $this->Partners->find()
            ->select(['id', 'logo_data', 'logo_mime_type'])
            ->where(['id' => (int)$id])
            ->enableHydration(false)
            ->first();

        if (!$partner || empty($partner['logo_mime_type'])) {
            throw new NotFoundException('Logo não encontrada.');
        }

        $data = $partner['logo_data'];

        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        return $this->response
            ->withType($partner['logo_mime_type'])
            ->withHeader('Cache-Control', 'public, max-age=86400')
            ->withHeader('Content-Disposition', 'inline')
            ->withStringBody((string)$data);
    }
}
