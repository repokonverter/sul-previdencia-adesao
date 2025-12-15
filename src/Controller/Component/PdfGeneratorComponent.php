<?php

declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use DateTime;
use Dompdf\Dompdf;

class PdfGeneratorComponent extends Component
{
    protected $AdhesionInitialDatas;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->AdhesionInitialDatas = TableRegistry::getTableLocator()->get('AdhesionInitialDatas');
    }

    public function generatePdf($id, $returnContent = false)
    {
        $adhesion = $this->AdhesionInitialDatas->get($id, [
            'contain' => [
                'AdhesionPersonalDatas',
                'AdhesionAddresses',
                'AdhesionDependents',
                'AdhesionPlans',
                'AdhesionDocuments',
                'AdhesionOtherInformations',
                'AdhesionPaymentDetails',
                'AdhesionPensionSchemes',
                'AdhesionProponentStatements'
            ]
        ]);

        $birthDate = new DateTime($adhesion->adhesion_personal_data->birth_date->format('Y-m-d'));
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;

        $proposalNumber = 'BFX-' . $id . '-' . date('m-Y');

        $controller = $this->getController();
        $controller->set(compact('adhesion', 'age', 'proposalNumber'));
        $builder = $controller->viewBuilder();
        $oldLayout = $builder->getLayout();
        $builder->disableAutoLayout();

        $html = (string)$controller->render('/layout/pdf/pdf_template')->getBody();

        if ($oldLayout) {
            $builder->setLayout($oldLayout);
        }
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if ($returnContent) {
            return $dompdf->output();
        }

        return $dompdf->stream("proposta-plenoprev-$proposalNumber.pdf", [
            'Attachment' => true
        ]);
    }

    public function generateFormPdf($id, $returnContent = false)
    {
        $adhesion = $this->AdhesionInitialDatas->get($id, [
            'contain' => [
                'AdhesionPersonalDatas',
                'AdhesionAddresses',
                'AdhesionDependents',
                'AdhesionPlans',
                'AdhesionDocuments',
                'AdhesionOtherInformations',
                'AdhesionPaymentDetails',
                'AdhesionPensionSchemes',
                'AdhesionProponentStatements'
            ]
        ]);

        $controller = $this->getController();
        $controller->set(compact('adhesion'));

        $builder = $controller->viewBuilder();
        $oldLayout = $builder->getLayout();
        $builder->disableAutoLayout();

        $html = (string)$controller->render('/layout/pdf/pdf_form_template')->getBody();

        if ($oldLayout) {
            $builder->setLayout($oldLayout);
        }

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if ($returnContent) {
            return $dompdf->output();
        }

        return $dompdf->stream("formulario-inscricao-$id.pdf", [
            "Attachment" => true
        ]);
    }
}
