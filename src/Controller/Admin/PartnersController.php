<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
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

    public function initialize(): void
    {
        parent::initialize();

        $this->Partners = $this->fetchTable('Partners');
        $this->PromotionalCodes = $this->fetchTable('PromotionalCodes');

        $this->paginate = [
            'order' => ['Partners.name' => 'ASC'],
            'limit' => 20,
        ];
    }

    public function index()
    {
        $query = $this->Partners->find('withAdhesionCounts');

        $q = $this->request->getQuery('q');

        if ($q) {
            $query->where([
                'OR' => [
                    'Partners.name LIKE' => "%$q%",
                    'Partners.id IN' => $this->PromotionalCodes->find()
                        ->select(['partner_id'])
                        ->where(['PromotionalCodes.code LIKE' => "%$q%"]),
                ],
            ]);
        }

        $partners = $this->paginate($query);

        $this->set(compact('partners'));
    }

    public function view($id = null)
    {
        $partner = $this->Partners->get($id);

        $promotionalCodes = $this->PromotionalCodes->find('withAdhesionCounts')
            ->where(['PromotionalCodes.partner_id' => $partner->id])
            ->orderBy(['PromotionalCodes.code' => 'ASC'])
            ->all();

        $this->set(compact('partner', 'promotionalCodes'));
    }

    public function add()
    {
        $partner = $this->Partners->newEntity(['color' => \App\Model\Entity\Partner::DEFAULT_COLOR]);

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

        $adhesions = $this->PromotionalCodes->AdhesionInitialDatas->find()
            ->innerJoinWith('PromotionalCodes')
            ->where(['PromotionalCodes.partner_id' => $partner->id])
            ->count();

        if ($adhesions > 0) {
            $this->Flash->error(__(
                'Este parceiro tem código(s) usado(s) em {0} adesão(ões) e não pode ser excluído. Desative-o para impedir novos usos.',
                $adhesions
            ));

            return $this->redirect(['action' => 'index']);
        }

        if ($this->Partners->delete($partner)) {
            $this->Flash->success(__('O parceiro foi removido com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível remover o parceiro. Por favor, tente novamente.'));
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
        $partner->active = !$partner->active;

        if ($this->Partners->save($partner)) {
            $this->Flash->success(__(
                'O parceiro {0} foi {1}.',
                $partner->name,
                $partner->active ? 'ativado' : 'desativado'
            ));
        } else {
            $this->Flash->error(__('Não foi possível alterar o parceiro.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function addCode($partnerId = null)
    {
        $partner = $this->Partners->get($partnerId);
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

        $promotionalCode = $this->PromotionalCodes->get($id);
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

        $promotionalCode = $this->PromotionalCodes->get($id);
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

    /**
     * Campos do formulário de parceiro, sem o arquivo de logo (tratado à
     * parte) e sem o flag de remoção.
     */
    protected function formData(): array
    {
        $data = $this->request->getData();

        unset($data['logo_file'], $data['remove_logo']);

        $data['active'] = (bool)($data['active'] ?? false);

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
