<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;
use App\Model\Entity\User;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

class AppController extends BaseController
{
    /**
     * Ações que o perfil `user` pode executar neste controller; o admin pode
     * todas. Nega por padrão: ação nova fica só para o admin até ser listada
     * aqui de propósito.
     *
     * @var array<string>
     */
    protected array $userActions = [];

    private ?bool $isAdmin = null;

    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setLayout('admin');

        $this->loadComponent('Authentication.Authentication');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // Permite acesso à tela de login sem estar autenticado
        $this->Authentication->addUnauthenticatedActions(['login']);

        $action = $this->request->getParam('action');
        if (in_array($action, $this->Authentication->getUnauthenticatedActions(), true)) {
            return;
        }

        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            // O AuthenticationComponent barra no Controller.startup.
            return;
        }

        // Perfil lido do banco, não da sessão: a sessão de quem logou antes da
        // coluna existir não tem `role`, e rebaixar um admin precisa valer já.
        $user = $this->fetchTable('Users')->find()
            ->select(['id', 'role'])
            ->where(['id' => $identity->getIdentifier()])
            ->first();

        if ($user === null) {
            $this->Authentication->logout();
            $event->setResult($this->redirect(['controller' => 'Users', 'action' => 'login']));

            return;
        }

        $this->isAdmin = $user->role === User::ROLE_ADMIN;

        if ($this->isAdmin || in_array($action, $this->userActions, true)) {
            return;
        }

        $event->setResult($this->denyAccess());
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        $this->set('isAdmin', $this->isAdmin());
    }

    protected function isAdmin(): bool
    {
        return $this->isAdmin === true;
    }

    /**
     * @throws \Cake\Http\Exception\ForbiddenException Em chamadas AJAX/JSON,
     *   onde um redirect com flash não chegaria a ninguém.
     */
    protected function denyAccess(): ?\Cake\Http\Response
    {
        $message = 'Seu perfil não tem permissão para esta ação.';

        if ($this->request->is(['ajax', 'json'])) {
            throw new ForbiddenException($message);
        }

        $this->Flash->error($message);

        return $this->redirect(['controller' => 'Simulations', 'action' => 'index']);
    }
}
