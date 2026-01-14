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

    public function generatePdfApplicationForm($id, $returnContent = false)
    {
        $adhesion = $this->AdhesionInitialDatas->get(
            $id,
            contain: [
                'AdhesionPersonalDatas',
                'AdhesionDocuments',
                'AdhesionPlans',
                'AdhesionDependents',
                'AdhesionAddresses',
                'AdhesionOtherInformations',
                'AdhesionProponentStatements',
                'AdhesionPensionSchemes',
                'AdhesionPaymentDetails',
                'ClicksignDatas',
                'PixTransactions',
            ]
        );
        $fixedData = [
            'cnpjPlan' => '48.307.525/0001-09',
            'institutionNumber' => '009',
            'institutionName' => 'CEPREV',
            'useInsuranceCompany' => [
                'code' => '100350',
                'membershipAgreement' => 'AD2550',
                'marketingAction' => 'AM0493',
                'branch' => 'F22',
                'alternative' => '01',
                'secureBroker1' => 'MT 8002897',
            ],
            'useSecureBroker' => [
                'name' => 'Corretop Corretora de Seguros',
                'code' => '202104784',
            ],
            'footer' => [
                'planManager' => 'Sociedade de Previdência Complementar Sul Previdência',
                'cnpjPlanManager' => '12.148.125/0001-42',
                'address' => 'Rua Vidal Ramos nº 31 - Sala 602 - Centro - Florianópolis - SC',
                'site' => 'www.sulprevidencia.org.br'
            ]
        ];
        $birthDate = new DateTime($adhesion->adhesion_personal_data->birth_date->format('Y-m-d'));
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        $proposalNumber = 'BFX-' . $id . '-' . date('m-Y');
        $controller = $this->getController();
        $controller->set(compact('adhesion', 'age', 'proposalNumber', 'fixedData'));
        $builder = $controller->viewBuilder();
        $oldLayout = $builder->getLayout();
        $builder->disableAutoLayout();

        $html = (string)$controller->render('/layout/pdf/pdf_template')->getBody();

        if ($oldLayout)
            $builder->setLayout($oldLayout);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if ($returnContent)
            return $dompdf->output();

        return $dompdf->stream("proposta-plenoprev-$proposalNumber.pdf", [
            'Attachment' => true
        ]);
    }

    public function generateRegistrationFormPdf($id, $returnContent = false)
    {
        $adhesion = $this->AdhesionInitialDatas->get(
            $id,
            contain: [
                'AdhesionPersonalDatas',
                'AdhesionDocuments',
                'AdhesionPlans',
                'AdhesionDependents',
                'AdhesionAddresses',
                'AdhesionOtherInformations',
                'AdhesionProponentStatements',
                'AdhesionPensionSchemes',
                'AdhesionPaymentDetails',
                'ClicksignDatas',
                'PixTransactions',
            ]
        );
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
