<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Model\Table\BrokersTable;
use App\Model\Table\PartnersTable;
use App\Model\Table\PromotionalCodesTable;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @property \App\Model\Table\PartnersTable $Partners
 */
class PartnersController extends AppController
{
    protected PartnersTable $Partners;
    protected PromotionalCodesTable $PromotionalCodes;
    protected BrokersTable $Brokers;

    /**
     * Falso aqui (tela "Parceiros"); AssociationsController sobrescreve para
     * true (tela "Vínculos associativos"). Os dois conjuntos são disjuntos e
     * compartilham este mesmo controller e os mesmos templates.
     */
    protected bool $isAssociationScope = false;

    protected array $userActions = ['index', 'view'];

    public function initialize(): void
    {
        parent::initialize();

        $this->Partners = $this->fetchTable('Partners');
        $this->PromotionalCodes = $this->fetchTable('PromotionalCodes');
        $this->Brokers = $this->fetchTable('Brokers');

        $this->paginate = [
            'order' => ['Partners.name' => 'ASC'],
            'limit' => 20,
        ];

        $this->set('isAssociationScope', $this->isAssociationScope);
        $this->set('entityLabelPlural', $this->isAssociationScope ? 'Vínculos associativos' : 'Parceiros');
        $this->set('entityLabelSingular', $this->isAssociationScope ? 'Vínculo associativo' : 'Parceiro');
    }

    /**
     * Rótulo usado nas mensagens flash ("o parceiro" / "o vínculo
     * associativo"). Ambos são masculinos, então o artigo não muda.
     */
    protected function entityLabel(): string
    {
        return $this->isAssociationScope ? 'vínculo associativo' : 'parceiro';
    }

    /**
     * Impede que a URL de uma tela alcance um registro do outro conjunto,
     * por exemplo abrindo /admin/associations/view/<id-de-um-parceiro-comum>.
     */
    protected function assertScope(\App\Model\Entity\Partner $partner): void
    {
        if ($partner->is_association !== $this->isAssociationScope) {
            throw new \Cake\Http\Exception\NotFoundException();
        }
    }

    /**
     * Corretor é só de parceiro comum: vínculo associativo não tem a seção,
     * e a URL de /admin/associations não alcança as ações dela.
     */
    protected function assertBrokerScope(\App\Model\Entity\Partner $partner): void
    {
        $this->assertScope($partner);

        if ($partner->is_association) {
            throw new \Cake\Http\Exception\NotFoundException();
        }
    }

    public function index()
    {
        $query = $this->Partners->find('withAdhesionCounts')
            ->find('byAssociationScope', isAssociation: $this->isAssociationScope);

        $q = $this->request->getQuery('q');

        if ($q) {
            $query->where([
                // Cada condição no próprio array: as duas de código e de
                // corretor têm a mesma chave, e num array só a segunda
                // apagaria a primeira.
                'OR' => [
                    ['Partners.name LIKE' => "%$q%"],
                    ['Partners.id IN' => $this->PromotionalCodes->find()
                        ->select(['partner_id'])
                        ->where(['PromotionalCodes.code LIKE' => "%$q%"])],
                    ['Partners.id IN' => $this->Brokers->find()
                        ->select(['partner_id'])
                        ->where(['Brokers.name ILIKE' => "%$q%"])],
                ],
            ]);
        }

        $partners = $this->paginate($query);

        $this->set(compact('partners'));
    }

    public function view($id = null)
    {
        $partner = $this->Partners->get($id);
        $this->assertScope($partner);

        $promotionalCodes = $this->PromotionalCodes->find('withAdhesionCounts')
            ->where(['PromotionalCodes.partner_id' => $partner->id])
            ->orderBy(['PromotionalCodes.code' => 'ASC'])
            ->all();

        $brokers = $partner->is_association ? [] : $this->Brokers->find('withAdhesionCounts')
            ->where(['Brokers.partner_id' => $partner->id])
            ->orderBy(['Brokers.name' => 'ASC'])
            ->all();

        $this->set(compact('partner', 'promotionalCodes', 'brokers'));
    }

    public function add()
    {
        $partner = $this->Partners->newEntity([
            'color' => \App\Model\Entity\Partner::DEFAULT_COLOR,
            'is_association' => $this->isAssociationScope,
        ]);

        if ($this->request->is('post')) {
            $partner = $this->Partners->patchEntity($partner, $this->formData());

            if ($this->saveWithLogo($partner)) {
                return $this->redirect(['action' => 'view', $partner->id]);
            }
        }

        $this->set(compact('partner'));
    }

    public function edit($id = null)
    {
        $partner = $this->Partners->get($id);
        $this->assertScope($partner);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $partner = $this->Partners->patchEntity($partner, $this->formData());

            if ($this->request->getData('remove_logo')) {
                $this->Partners->clearLogo($partner);
            }

            if ($this->saveWithLogo($partner)) {
                return $this->redirect(['action' => 'view', $partner->id]);
            }
        }

        $this->set(compact('partner'));
    }

    /**
     * Um parceiro só pode ser excluído se nenhum dos seus códigos jamais
     * tiver sido usado em uma adesão; os códigos são excluídos junto.
     * Havendo qualquer uso, o caminho é desativar.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $partner = $this->Partners->get($id);
        $this->assertScope($partner);

        $adhesions = $this->PromotionalCodes->AdhesionInitialDatas->find()
            ->innerJoinWith('PromotionalCodes')
            ->where(['PromotionalCodes.partner_id' => $partner->id])
            ->count();

        $adhesionsViaAssociation = $this->Partners->AdhesionInitialDatas->find()
            ->where(['AdhesionInitialDatas.association_partner_id' => $partner->id])
            ->count();

        // A FK de adhesion_initial_data.broker_id é RESTRICT: sem esta
        // checagem, o CASCADE de partners para brokers estouraria no banco.
        $adhesionsViaBroker = $this->Brokers->AdhesionInitialDatas->find()
            ->innerJoinWith('Brokers')
            ->where(['Brokers.partner_id' => $partner->id])
            ->count();

        if ($adhesions > 0 || $adhesionsViaAssociation > 0 || $adhesionsViaBroker > 0) {
            $this->Flash->error(__(
                'Este {0} tem código(s) ou corretor(es) usado(s) em {1} adesão(ões) e não pode ser excluído. Desative-o para impedir novos usos.',
                $this->entityLabel(),
                max($adhesions, $adhesionsViaAssociation, $adhesionsViaBroker)
            ));

            return $this->redirect(['action' => 'index']);
        }

        if ($this->Partners->delete($partner)) {
            $this->Flash->success(__('O {0} foi removido com sucesso.', $this->entityLabel()));
        } else {
            $this->Flash->error(__('Não foi possível remover o {0}. Por favor, tente novamente.', $this->entityLabel()));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Alterna o estado ativo/inativo do parceiro sem passar pelo formulário completo.
     */
    public function toggle($id = null)
    {
        $this->request->allowMethod(['post']);

        $partner = $this->Partners->get($id);
        $this->assertScope($partner);

        $partner->active = !$partner->active;

        if ($this->Partners->save($partner)) {
            $this->Flash->success(__(
                'O {0} {1} foi {2}.',
                $this->entityLabel(),
                $partner->name,
                $partner->active ? 'ativado' : 'desativado'
            ));
        } else {
            $this->Flash->error(__('Não foi possível alterar o {0}.', $this->entityLabel()));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function addCode($partnerId = null)
    {
        $partner = $this->Partners->get($partnerId);
        $this->assertScope($partner);

        $promotionalCode = $this->PromotionalCodes->newEntity(['partner_id' => $partner->id]);

        if ($this->request->is('post')) {
            $promotionalCode = $this->PromotionalCodes->patchEntity(
                $promotionalCode,
                $this->codeFormData($partner->id)
            );

            if ($this->PromotionalCodes->save($promotionalCode)) {
                $this->Flash->success(__('O código promocional foi salvo com sucesso.'));

                return $this->redirect(['action' => 'view', $partner->id]);
            }

            $this->Flash->error(__('Não foi possível salvar o código promocional. Por favor, tente novamente.'));
        }

        $this->set(compact('partner', 'promotionalCode'));
    }

    public function editCode($id = null)
    {
        $promotionalCode = $this->PromotionalCodes->get($id, contain: ['Partners']);
        $partner = $promotionalCode->partner;
        $this->assertScope($partner);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $promotionalCode = $this->PromotionalCodes->patchEntity(
                $promotionalCode,
                $this->codeFormData($partner->id)
            );

            if ($this->PromotionalCodes->save($promotionalCode)) {
                $this->Flash->success(__('O código promocional foi salvo com sucesso.'));

                return $this->redirect(['action' => 'view', $partner->id]);
            }

            $this->Flash->error(__('Não foi possível salvar o código promocional. Por favor, tente novamente.'));
        }

        $this->set(compact('partner', 'promotionalCode'));
    }

    /**
     * Códigos já usados em adesões não podem ser excluídos: isso destruiria o
     * histórico. O caminho para impedir novos usos é desativar.
     */
    public function deleteCode($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $promotionalCode = $this->PromotionalCodes->get($id, contain: ['Partners']);
        $this->assertScope($promotionalCode->partner);
        $partnerId = $promotionalCode->partner_id;

        $adhesions = $this->PromotionalCodes->AdhesionInitialDatas->find()
            ->where(['promotional_code_id' => $promotionalCode->id])
            ->count();

        if ($adhesions > 0) {
            $this->Flash->error(__(
                'Este código foi usado em {0} adesão(ões) e não pode ser excluído. Desative-o para impedir novos usos.',
                $adhesions
            ));

            return $this->redirect(['action' => 'view', $partnerId]);
        }

        if ($this->PromotionalCodes->delete($promotionalCode)) {
            $this->Flash->success(__('O código promocional foi removido com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível remover o código promocional. Por favor, tente novamente.'));
        }

        return $this->redirect(['action' => 'view', $partnerId]);
    }

    /**
     * Alterna o estado ativo/inativo do código sem passar pelo formulário completo.
     */
    public function toggleCode($id = null)
    {
        $this->request->allowMethod(['post']);

        $promotionalCode = $this->PromotionalCodes->get($id, contain: ['Partners']);
        $this->assertScope($promotionalCode->partner);
        $promotionalCode->active = !$promotionalCode->active;

        if ($this->PromotionalCodes->save($promotionalCode)) {
            $this->Flash->success(__(
                'O código {0} foi {1}.',
                $promotionalCode->code,
                $promotionalCode->active ? 'ativado' : 'desativado'
            ));
        } else {
            $this->Flash->error(__('Não foi possível alterar o código promocional.'));
        }

        return $this->redirect(['action' => 'view', $promotionalCode->partner_id]);
    }

    public function addBroker($partnerId = null)
    {
        $partner = $this->Partners->get($partnerId);
        $this->assertBrokerScope($partner);

        $broker = $this->Brokers->newEntity(['partner_id' => $partner->id]);

        if ($this->request->is('post')) {
            $broker = $this->Brokers->patchEntity($broker, $this->brokerFormData($partner->id));

            if ($this->Brokers->save($broker)) {
                $this->Flash->success(__('O corretor foi salvo com sucesso.'));

                return $this->redirect(['action' => 'view', $partner->id]);
            }

            $this->Flash->error(__('Não foi possível salvar o corretor. Por favor, tente novamente.'));
        }

        $this->set(compact('partner', 'broker'));
    }

    public function editBroker($id = null)
    {
        $broker = $this->Brokers->get($id, contain: ['Partners']);
        $partner = $broker->partner;
        $this->assertBrokerScope($partner);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $broker = $this->Brokers->patchEntity($broker, $this->brokerFormData($partner->id));

            if ($this->Brokers->save($broker)) {
                $this->Flash->success(__('O corretor foi salvo com sucesso.'));

                return $this->redirect(['action' => 'view', $partner->id]);
            }

            $this->Flash->error(__('Não foi possível salvar o corretor. Por favor, tente novamente.'));
        }

        $this->set(compact('partner', 'broker'));
    }

    /**
     * Mesma regra dos códigos: corretor já usado em adesão não sai, só é
     * desativado.
     */
    public function deleteBroker($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $broker = $this->Brokers->get($id, contain: ['Partners']);
        $this->assertBrokerScope($broker->partner);
        $partnerId = $broker->partner_id;

        $adhesions = $this->Brokers->AdhesionInitialDatas->find()
            ->where(['broker_id' => $broker->id])
            ->count();

        if ($adhesions > 0) {
            $this->Flash->error(__(
                'Este corretor foi usado em {0} adesão(ões) e não pode ser excluído. Desative-o para impedir novos usos.',
                $adhesions
            ));

            return $this->redirect(['action' => 'view', $partnerId]);
        }

        if ($this->Brokers->delete($broker)) {
            $this->Flash->success(__('O corretor foi removido com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível remover o corretor. Por favor, tente novamente.'));
        }

        return $this->redirect(['action' => 'view', $partnerId]);
    }

    public function toggleBroker($id = null)
    {
        $this->request->allowMethod(['post']);

        $broker = $this->Brokers->get($id, contain: ['Partners']);
        $this->assertBrokerScope($broker->partner);
        $broker->active = !$broker->active;

        if ($this->Brokers->save($broker)) {
            $this->Flash->success(__(
                'O corretor {0} foi {1}.',
                $broker->name,
                $broker->active ? 'ativado' : 'desativado'
            ));
        } else {
            $this->Flash->error(__('Não foi possível alterar o corretor.'));
        }

        return $this->redirect(['action' => 'view', $broker->partner_id]);
    }

    /**
     * Liga/desliga a marca do parceiro ("em parceria com...") quando o
     * participante escolhe um corretor dele. Vale na hora, inclusive para
     * adesões em andamento: a marca não é gravada na adesão.
     */
    public function toggleBrokerPartnership($id = null)
    {
        $this->request->allowMethod(['post']);

        $partner = $this->Partners->get($id);
        $this->assertBrokerScope($partner);
        $partner->brokers_show_partnership = !$partner->brokers_show_partnership;

        if ($this->Partners->save($partner)) {
            $this->Flash->success($partner->brokers_show_partnership
                ? __('Os corretores de {0} passam a mostrar "em parceria com {0}".', $partner->name)
                : __('Os corretores de {0} não mostram mais "em parceria com {0}".', $partner->name));
        } else {
            $this->Flash->error(__('Não foi possível alterar a opção.'));
        }

        return $this->redirect(['action' => 'view', $partner->id]);
    }

    /**
     * Campos do formulário de parceiro, sem o arquivo de logo (tratado à
     * parte) e sem o flag de remoção.
     */
    protected function formData(): array
    {
        $data = $this->request->getData();

        unset($data['logo_file'], $data['remove_logo']);

        $data['active'] = (bool)($data['active'] ?? false);
        // Não vem do formulário: é implícito na tela usada para chegar aqui
        // (Parceiros vs. Vínculos associativos), nunca no que o navegador envia.
        $data['is_association'] = $this->isAssociationScope;

        return $data;
    }

    /**
     * Campos do formulário de código promocional.
     */
    protected function codeFormData(int $partnerId): array
    {
        $data = $this->request->getData();

        $data['partner_id'] = $partnerId;
        $data['active'] = (bool)($data['active'] ?? false);

        foreach (['valid_from', 'valid_until'] as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * Campos do formulário de corretor. O parceiro vem da URL, nunca do
     * POST, e o token nem é aceito (fora de Broker::$_accessible).
     */
    protected function brokerFormData(int $partnerId): array
    {
        $data = $this->request->getData();

        $data['partner_id'] = $partnerId;
        $data['active'] = (bool)($data['active'] ?? false);

        return $data;
    }

    protected function saveWithLogo($partner): bool
    {
        $file = $this->request->getData('logo_file');

        if ($file instanceof UploadedFileInterface) {
            $error = $this->Partners->applyLogoUpload($partner, $file);

            if ($error !== null) {
                $this->Flash->error($error);

                return false;
            }
        }

        if ($this->Partners->save($partner)) {
            $this->Flash->success(__('O parceiro foi salvo com sucesso.'));

            return true;
        }

        $this->Flash->error(__('Não foi possível salvar o parceiro. Por favor, tente novamente.'));

        return false;
    }
}
