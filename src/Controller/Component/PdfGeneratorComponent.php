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
        $proposalNumber = $this->proposalNumber($id);
        $html = $this->applicationFormHtml($id);

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

    private function proposalNumber($id): string
    {
        return 'BFX-' . $id . '-' . date('m-Y');
    }

    /**
     * O HTML da proposta, antes de virar PDF.
     *
     * Existe separado para que o conteúdo do documento possa ser verificado
     * sem passar pelo dompdf: o que importa conferir é o que está escrito na
     * proposta que alguém vai assinar, e isso é texto, não um binário.
     */
    public function applicationFormHtml($id): string
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
                'address' => 'Rua Vidal Ramos nº 31 - Sala 1101 - Centro - Florianópolis - SC',
                'site' => 'www.sulprevidencia.org.br'
            ]
        ];
        $birthDate = new DateTime($adhesion->adhesion_personal_data->birth_date->format('Y-m-d'));
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        $proposalNumber = $this->proposalNumber($id);
        $controller = $this->getController();
        $controller->set(compact('adhesion', 'age', 'proposalNumber', 'fixedData'));
        $builder = $controller->viewBuilder();
        $oldLayout = $builder->getLayout();
        $builder->disableAutoLayout();

        $html = (string)$controller->render('/layout/pdf/pdf_template')->getBody();

        if ($oldLayout)
            $builder->setLayout($oldLayout);

        return $html;
    }

    /**
     * A ficha de inscrição CEPREV. Sempre a mesma, padrão -- vínculo
     * associativo não entra mais aqui, tem sua própria declaração (ver
     * generateAssociationDeclarationPdf).
     */
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

    /**
     * A Declaração de Vínculo Associativo: substitui a ficha de inscrição
     * quando a adesão tem um vínculo (association_partner_id). O snapshot
     * gravado na adesão (association_texts) tem prioridade sobre o cadastro
     * atual do parceiro -- reproduz sempre o que foi de fato assinado, mesmo
     * que o nome/CNPJ cadastrados no vínculo mudem depois. A logo e a cor,
     * por outro lado, vêm do cadastro atual: não fazem parte do que foi
     * assinado, são só a moldura visual do documento.
     */
    public function generateAssociationDeclarationPdf($id, $returnContent = false)
    {
        $adhesion = $this->AdhesionInitialDatas->get(
            $id,
            contain: ['AdhesionPersonalDatas', 'AssociationPartners']
        );

        $declaration = $adhesion->association_texts ?? $adhesion->association_partner?->associationDeclarationData() ?? [
            'companyName' => null,
            'companyCnpj' => null,
        ];

        $controller = $this->getController();
        $controller->set(compact('adhesion', 'declaration'));

        $builder = $controller->viewBuilder();
        $oldLayout = $builder->getLayout();
        $builder->disableAutoLayout();

        $html = (string)$controller->render('/layout/pdf/pdf_association_declaration')->getBody();

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

        return $dompdf->stream("declaracao-vinculo-associativo-$id.pdf", [
            "Attachment" => true
        ]);
    }
}
