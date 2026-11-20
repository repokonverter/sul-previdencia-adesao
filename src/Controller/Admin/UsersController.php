<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Model\Entity\PasswordReset;
use App\Model\Entity\User;
use App\Model\Table\PasswordResetsTable;
use App\Services\BenefitPlans;
use App\Services\EmailTemplates;
use App\Services\ResendService;
use App\Utility\PasswordPolicy;
use App\Utility\Throttle;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\DateTime;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    protected array $userActions = ['logout', 'changePassword'];

    // Limites de tentativa (Q5, Q15 e Q18 do planejamento). Janelas em segundos.
    private const LOGIN_MAX_PER_EMAIL = 5;
    private const LOGIN_MAX_PER_IP = 20;
    private const LOGIN_WINDOW = 15 * 60;
    private const FORGOT_MAX_PER_IP = 5;
    private const FORGOT_IP_WINDOW = 15 * 60;
    private const FORGOT_MAX_PER_EMAIL = 3;
    private const FORGOT_EMAIL_WINDOW = 60 * 60;
    private const CURRENT_PASSWORD_MAX = 5;
    private const CURRENT_PASSWORD_WINDOW = 15 * 60;

    private const NEUTRAL_FORGOT_MESSAGE = 'Se este e-mail estiver cadastrado, você receberá um link em instantes.';
    private const TOO_MANY_ATTEMPTS = 'Muitas tentativas. Tente novamente em alguns minutos ou use "Esqueci minha senha".';

    protected PasswordResetsTable $PasswordResets;

    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Authentication->allowUnauthenticated(['login', 'forgotPassword', 'resetPassword']);
        $this->PasswordResets = $this->fetchTable('PasswordResets');
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Users->find();
        
        $q = $this->request->getQuery('q');
        if ($q) {
            $query->where([
                'OR' => [
                    'Users.name LIKE' => "%$q%",
                    'Users.email LIKE' => "%$q%"
                ]
            ]);
        }

        $users = $this->paginate($query);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, contain: []);
        $this->set(compact('user'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(ResendService $mailer)
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->newInvitedUser($this->profileData());
            if ($this->Users->save($user)) {
                if ($this->sendPasswordLinkTo($user, $mailer)) {
                    $this->Flash->success(__('O usuário foi criado e recebeu por e-mail o link para definir a senha.'));
                } else {
                    $this->Flash->warning(__('O usuário foi criado, mas o e-mail com o link não pôde ser enviado. Use "Enviar link para definir senha" na tela do usuário.'));
                }

                return $this->redirect(['action' => 'view', $user->id]);
            }
            $this->Flash->error(__('Não foi possível salvar o usuário. Por favor, tente novamente.'));
        }
        $this->set(compact('user'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->profileData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('O usuário foi salvo com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Não foi possível salvar o usuário. Por favor, tente novamente.'));
        }
        $this->set(compact('user'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('O usuário foi removido com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível remover o usuário. Por favor, tente novamente.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function login()
    {
        $this->viewBuilder()->setLayout('auth');

        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        if (!$this->request->is('post')) {
            // Se já estiver autenticado, redireciona para o dashboard
            if ($result && $result->isValid()) {
                return $this->redirect($this->Authentication->getLoginRedirect() ?? '/admin');
            }

            return;
        }

        $emailKey = Throttle::key('login_email', (string)$this->request->getData('email'));
        $ipKey = Throttle::key('login_ip', (string)$this->request->clientIp());

        // O autenticador já rodou no middleware: bloqueado, nem a senha certa
        // vale, e a sessão que ele abriu é desfeita. A mensagem é a mesma
        // para e-mail cadastrado ou não.
        if (Throttle::tooMany($emailKey, self::LOGIN_MAX_PER_EMAIL) || Throttle::tooMany($ipKey, self::LOGIN_MAX_PER_IP)) {
            if ($result && $result->isValid()) {
                $this->Authentication->logout();
            }
            $this->Flash->error(__(self::TOO_MANY_ATTEMPTS));

            return;
        }

        if ($result && $result->isValid()) {
            Throttle::clear($emailKey);

            return $this->redirect($this->Authentication->getLoginRedirect() ?? '/admin');
        }

        Throttle::hit($emailKey, self::LOGIN_MAX_PER_EMAIL, self::LOGIN_WINDOW);
        Throttle::hit($ipKey, self::LOGIN_MAX_PER_IP, self::LOGIN_WINDOW);
        $this->Flash->error(__('Usuário ou senha incorretos.'));
    }

    /**
     * A resposta é sempre a mesma, exista o e-mail ou não, e também quando o
     * limite de pedidos estoura: ninguém descobre por aqui quem tem acesso.
     */
    public function forgotPassword(ResendService $mailer)
    {
        $this->viewBuilder()->setLayout('auth');
        $this->request->allowMethod(['get', 'post']);

        if (!$this->request->is('post')) {
            return;
        }

        $email = strtolower(trim((string)$this->request->getData('email')));
        $ipKey = Throttle::key('forgot_ip', (string)$this->request->clientIp());
        $emailKey = Throttle::key('forgot_email', $email);

        $allowed = $email !== ''
            && !Throttle::tooMany($ipKey, self::FORGOT_MAX_PER_IP)
            && !Throttle::tooMany($emailKey, self::FORGOT_MAX_PER_EMAIL);

        Throttle::hit($ipKey, self::FORGOT_MAX_PER_IP, self::FORGOT_IP_WINDOW);

        if ($email !== '') {
            Throttle::hit($emailKey, self::FORGOT_MAX_PER_EMAIL, self::FORGOT_EMAIL_WINDOW);
        }

        if ($allowed) {
            $user = $this->Users->find()->where(['LOWER(Users.email)' => $email])->first();

            if ($user !== null) {
                $this->sendPasswordLinkTo($user, $mailer);
            }
        }

        $this->Flash->success(__(self::NEUTRAL_FORGOT_MESSAGE));

        return $this->redirect(['action' => 'login']);
    }

    /**
     * O link do e-mail: redefinição ou convite. Depois de salvar, a pessoa
     * entra pelo login, não direto.
     */
    public function resetPassword(ResendService $mailer, ?string $token = null)
    {
        $this->viewBuilder()->setLayout('auth');
        $this->request->allowMethod(['get', 'post']);
        // O token está na URL: não vaza no Referer de nada que a página carregue.
        $this->response = $this->response->withHeader('Referrer-Policy', 'no-referrer');

        $reset = $this->PasswordResets->findUsable((string)$token);

        if ($reset === null) {
            $this->set('reset', null);

            return;
        }

        $user = $reset->user;

        if ($this->request->is('post')) {
            $changed = $this->Users->changePassword(
                $user,
                (string)$this->request->getData('new_password'),
                (string)$this->request->getData('confirm_password')
            );

            if ($changed) {
                $this->notifyPasswordChanged($user, $mailer);
                $this->Flash->success($reset->isInvite()
                    ? __('Senha definida. Entre com o seu e-mail e a nova senha.')
                    : __('Senha alterada. Entre com a nova senha.'));

                return $this->redirect(['action' => 'login']);
            }

            $this->Flash->error(__('Não foi possível salvar a senha. Confira os campos abaixo.'));
        }

        $this->set(compact('reset', 'user'));
        $this->set('personalWords', PasswordPolicy::personalWords($user->name, $user->email));
    }

    /**
     * "Alterar minha senha", logado. Pede a atual (quem achar o computador
     * aberto não troca), mantém esta sessão e derruba as outras.
     */
    public function changePassword(ResendService $mailer)
    {
        $this->request->allowMethod(['get', 'post']);

        $user = $this->Users->get($this->Authentication->getIdentity()->getIdentifier());

        if ($this->request->is('post')) {
            $key = Throttle::key('current_password', $user->id);

            if (Throttle::tooMany($key, self::CURRENT_PASSWORD_MAX)) {
                $this->Flash->error(__('Muitas tentativas com a senha atual errada. Tente novamente em alguns minutos.'));
            } elseif (!(new DefaultPasswordHasher())->check((string)$this->request->getData('current_password'), $user->password)) {
                Throttle::hit($key, self::CURRENT_PASSWORD_MAX, self::CURRENT_PASSWORD_WINDOW);
                $user->setError('current_password', [__('Senha atual incorreta.')]);
                $this->Flash->error(__('Não foi possível alterar a senha. Confira os campos abaixo.'));
            } elseif ($this->Users->changePassword(
                $user,
                (string)$this->request->getData('new_password'),
                (string)$this->request->getData('confirm_password')
            )) {
                Throttle::clear($key);
                // A sessão passa a carregar o novo password_changed_at; sem
                // isso, Admin\AppController derrubaria esta também.
                $this->Authentication->setIdentity($user);
                $this->notifyPasswordChanged($user, $mailer);
                $this->Flash->success(__('Senha alterada. As sessões abertas em outros navegadores foram encerradas.'));

                return $this->redirect('/admin');
            } else {
                $this->Flash->error(__('Não foi possível alterar a senha. Confira os campos abaixo.'));
            }
        }

        $this->set(compact('user'));
        $this->set('personalWords', PasswordPolicy::personalWords($user->name, $user->email));
    }

    /**
     * Botão da tela do usuário, só do admin: convite para quem ainda não
     * definiu a senha, redefinição para os outros. Reenviar invalida o link
     * anterior.
     */
    public function sendPasswordLink(ResendService $mailer, $id = null)
    {
        $this->request->allowMethod(['post']);

        $user = $this->Users->get($id);

        if ($this->sendPasswordLinkTo($user, $mailer)) {
            $this->Flash->success(__('Link enviado para {0}.', $user->email));
        } else {
            $this->Flash->error(__('Não foi possível enviar o e-mail. Tente novamente em instantes.'));
        }

        return $this->redirect(['action' => 'view', $user->id]);
    }

    public function logout()
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            $this->Authentication->logout();
        }

        return $this->redirect('/admin/users/login');
    }

    /**
     * Nome, e-mail e perfil: a senha nunca vem do formulário de cadastro.
     */
    private function profileData(): array
    {
        return array_intersect_key((array)$this->request->getData(), array_flip(['name', 'email', 'role']));
    }

    private function sendPasswordLinkTo(User $user, ResendService $mailer): bool
    {
        $purpose = $user->hasDefinedPassword() ? PasswordReset::PURPOSE_RESET : PasswordReset::PURPOSE_INVITE;
        $token = $this->PasswordResets->issue($user, $purpose, $this->request->clientIp());
        // Host fixo, o do plano principal: o usuário do admin não é de plano
        // nenhum, e o link não depende de em qual host o admin foi aberto.
        $url = BenefitPlans::primary()->url(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'resetPassword', $token]);
        $minutes = PasswordResetsTable::LIFETIMES[$purpose];

        [$subject, $html] = $purpose === PasswordReset::PURPOSE_INVITE
            ? ['Defina sua senha do painel da Sul Previdência', EmailTemplates::userInvite($user->name, $url, intdiv($minutes, 60))]
            : ['Redefinição de senha do painel da Sul Previdência', EmailTemplates::passwordReset($user->name, $url, $minutes)];

        return $mailer->withoutBodyInLogs()->send([$user->email], $subject, $html);
    }

    private function notifyPasswordChanged(User $user, ResendService $mailer): void
    {
        $mailer->send(
            [$user->email],
            'Sua senha do painel da Sul Previdência foi alterada',
            EmailTemplates::passwordChanged($user->name, DateTime::now()->i18nFormat('dd/MM/yyyy HH:mm'))
        );
    }
}
