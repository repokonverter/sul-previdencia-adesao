<?php
/**
 * templates/Pages/home.php
 * Página principal convertida de index.html.erb para CakePHP
 *
 * Antes de usar, confirme que:
 * - webroot/css/c65a9f7a-d65c-4901-b613-a628a13dd473.css existe
 * - webroot/js/2002d4fd-df95-4062-88d8-8c824e529f36.js existe
 * - imagens estão em webroot/img/
 */
?>
<!-- Importa CSS específico desta página -->
<?= $this->Html->css('application') ?>

<div class="app-container" style="background: #fff !important;">
  <div class="fintech-header">
    <?= $this->Html->image('logo_sul_transparente.png', ['alt' => 'Sul Previdência', 'class' => 'fintech-logo-right']) ?>
  </div>

  <div class="app-content">
    <div class="fintech-hero fintech-hero-left">
      <h1>
        <span style="color:#222;">Escolha inteligente.</span><br>
        <span class="laranja" style="color:#F47C20;opacity:1;">Escolha Sul Previdência.</span>
      </h1>
      <p class="fintech-subtitle" style="color:#F47C20;">A previdência digital, simples e transparente para você.</p>
      <div class="botoes fintech-botoes">
        <a href="#" class="fintech-btn fintech-btn-lg btn-laranja" id="abrir-simulador-modal">Simular minha previdência</a>
        <a href="#" class="fintech-btn fintech-btn-lg btn-laranja" id="iniciar-btn">Faça sua adesão</a>
      </div>
    </div>

    <?= $this->Form->create(null, [
        'url' => ['controller' => 'Cadastros', 'action' => 'add'],
        'class' => 'register-screen',
        'id' => 'register-form',
        'style' => 'display: none;'
    ]) ?>
      <h2 class="register-title">Adquira sua Previdência</h2>

      <!-- Mensagem de erro via Flash (se usar depois) -->
      <?php if ($this->Flash->render('error')): ?>
        <div class="error-message" style="color: #FF0000; text-align: center; margin-bottom: 20px;">
          <?= $this->Flash->render('error') ?>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <?= $this->Form->control('nome', [
          'label' => 'Nome Completo',
          'placeholder' => 'Digite seu nome completo',
          'class' => 'form-input',
          'templates' => ['inputContainer' => '{{content}}']
        ]) ?>
      </div>

      <div class="form-group">
        <?= $this->Form->control('cpf', [
          'label' => 'CPF',
          'placeholder' => '000.000.000-00',
          'class' => 'form-input',
          'id' => 'cpf-input',
          'templates' => ['inputContainer' => '{{content}}']
        ]) ?>
      </div>

      <div class="form-group">
        <?= $this->Form->control('data_nascimento', [
          'label' => 'Data de Nascimento',
          'class' => 'form-input',
          'type' => 'date',
          'templates' => ['inputContainer' => '{{content}}']
        ]) ?>
      </div>

      <div class="form-group">
        <?= $this->Form->control('plano', [
          'label' => 'Plano',
          'type' => 'select',
          'options' => ['pleno_prev' => 'Pleno Prev'],
          'empty' => 'Selecione um plano',
          'class' => 'form-input',
          'templates' => ['inputContainer' => '{{content}}']
        ]) ?>
      </div>

      <div class="form-group">
        <?= $this->Form->control('email', [
          'label' => 'E-mail',
          'placeholder' => 'Digite seu e-mail',
          'class' => 'form-input',
          'type' => 'email',
          'templates' => ['inputContainer' => '{{content}}']
        ]) ?>
      </div>

      <div class="form-group">
        <?= $this->Form->control('celular', [
          'label' => 'Celular',
          'placeholder' => '(99) 99999-9999',
          'class' => 'form-input',
          'id' => 'celular-input',
          'templates' => ['inputContainer' => '{{content}}']
        ]) ?>
      </div>

      <div class="form-group">
        <?= $this->Form->control('valor_contribuicao', [
          'label' => 'Valor da Contribuição Mensal (R$)',
          'placeholder' => 'Ex: 100.00',
          'class' => 'form-input',
          'type' => 'number',
          'min' => 1,
          'step' => '0.01',
          'required' => true,
          'templates' => ['inputContainer' => '{{content}}']
        ]) ?>
      </div>

      <?= $this->Form->button('Enviar cadastro', [
        'class' => 'submit-button',
        'id' => 'submit-cadastro-btn',
        'data-disable-with' => 'Processando...'
      ]) ?>

    <?= $this->Form->end() ?>
  </div>

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="loading-overlay hidden" style="display:none;">
    <div class="loading-content">
      <div class="loading-spinner"></div>
      <h3 class="loading-title">Processando seu cadastro...</h3>
      <p class="loading-message">
        Estamos registrando suas informações e gerando o QR Code para pagamento.
        <br>Isso pode levar alguns segundos.
      </p>
      <div class="loading-steps">
        <div class="step active" id="step-1">
          <span class="step-icon">📝</span>
          <span class="step-text">Validando dados</span>
        </div>
        <div class="step" id="step-2">
          <span class="step-icon">💳</span>
          <span class="step-text">Gerando PIX</span>
        </div>
        <div class="step" id="step-3">
          <span class="step-icon">📧</span>
          <span class="step-text">Enviando emails</span>
        </div>
      </div>
    </div>
  </div>

  <!-- CPF Confirmation Modal -->
  <div id="cpf-confirmation-modal" class="loading-overlay hidden" style="display:none;">
    <div class="loading-content" style="max-width: 500px;">
      <div style="text-align: center; margin-bottom: 20px;">
        <div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>
        <h3 style="color: #dc3545; margin-bottom: 15px;">CPF Inválido Detectado</h3>
      </div>
      <div style="text-align: left; margin-bottom: 25px;">
        <p style="color: #666; line-height: 1.5; margin-bottom: 15px;">
          O CPF informado (<strong id="cpf-modal-value"></strong>) não passou na validação matemática.
        </p>
        <p style="color: #666; line-height: 1.5; margin-bottom: 15px;">
          <strong>Isso pode causar problemas:</strong>
        </p>
        <ul style="color: #666; line-height: 1.5; margin-left: 20px;">
          <li>O QR Code PIX pode não ser gerado</li>
          <li>O pagamento pode ser rejeitado</li>
          <li>Problemas na documentação futura</li>
        </ul>
      </div>
      <div style="text-align: center;">
        <p style="color: #333; font-weight: 600; margin-bottom: 20px;">
          Deseja continuar mesmo assim ou corrigir o CPF?
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
          <button id="corrigir-cpf-btn" class="modal-button" style="background-color: #28a745;">✏️ Corrigir CPF</button>
          <button id="continuar-cpf-btn" class="modal-button" style="background-color: #dc3545;">⚠️ Continuar Assim</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div class="success-modal" id="success-modal" style="display:none;">
    <div class="modal-content">
      <div class="modal-content-inner">
        <h3 class="modal-title">Cadastro realizado!</h3>
        <p class="modal-message">Sua inscrição foi realizada com sucesso. Nossa equipe entrará em contato para coletar mais informações.</p>

        <!-- QR e BR code simulado via flash (manter front) -->
        <?php if ($this->Flash->render('qr_code_image') || $this->Flash->render('br_code')): ?>
          <div class="qr-code-container" style="text-align: center; margin: 20px 0;">
            <?php if ($this->Flash->render('qr_code_image')): ?>
              <img src="<?= h($this->Flash->render('qr_code_image')) ?>" alt="QR Code para Pagamento Pix" style="width:200px;height:200px;border:1px solid #ddd;border-radius:8px;">
            <?php endif; ?>
            <p class="modal-message" style="margin-top:10px;">Ou copie o código Pix abaixo:</p>
            <textarea id="pix-code" readonly style="width:100%;height:60px;resize:none;border-radius:8px;padding:8px;font-size:14px;"><?= h($this->Flash->render('br_code') ?: 'Código Pix não disponível') ?></textarea>
            <button onclick="copyPixCode()" class="modal-button" style="margin-top:10px;background-color:#FF6200;color:white;">Copiar Código Pix</button>

            <div class="email-notice" style="background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%); border: 1px solid #2196F3; border-radius: 12px; padding: 18px; margin: 25px 0;">
              <div style="display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
                <span style="font-size:28px;margin-right:12px;">📧</span>
                <h4 style="color:#1976D2;margin:0;font-size:18px;font-weight:600;">Importante!</h4>
              </div>
              <p style="color:#333;margin:0;font-size:15px;line-height:1.4;text-align:center;">
                <strong>Verifique seu email</strong> para acessar informações importantes sobre sua adesão e documentos que precisam ser preenchidos.
              </p>
            </div>
          </div>
        <?php endif; ?>

        <button class="modal-button" id="close-modal">Entendi</button>
      </div>
    </div>
  </div>

  <!-- Simulador Modal -->
  <div id="simulador-modal" class="modal" style="display:none;">
    <div class="modal-content">
      <div class="modal-content-inner">
        <h2>Simulador de Previdência Privada</h2>
        <form id="simulador-form" data-turbo="false">
          <div class="form-group">
            <label for="data_nascimento">Data de Nascimento</label>
            <input type="date" id="data_nascimento" name="data_nascimento" required>
          </div>
          <div class="form-group">
            <label for="valor_investimento">Investimento Total Mensal</label>
            <input type="number" id="valor_investimento" name="valor_investimento" step="0.01" placeholder="R$ 100,00" required>
          </div>
          <div style="margin-top:16px;">
            <button type="button" class="modal-button" id="simular-btn">Simular</button>
            <button type="button" class="modal-button" id="fechar-simulador">Fechar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- App Navigation -->
  <div class="app-nav">
    <div class="nav-item">
      <a href="https://www.instagram.com/sulprevidencia/" target="_blank" rel="noopener" style="text-decoration:none;display:flex;flex-direction:column;align-items:center;">
        <!-- SVG mantido -->
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" stroke="#FF6200" stroke-width="2" fill="none"/>
          <circle cx="12" cy="12" r="4" stroke="#FF6200" stroke-width="2" fill="none"/>
          <circle cx="17" cy="7" r="1.5" fill="#FF6200"/>
        </svg>
        <span class="nav-label">Sobre</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="https://wa.me/5548991321918" target="_blank" rel="noopener" style="text-decoration:none;display:flex;flex-direction:column;align-items:center;">
        <!-- SVG WhatsApp mantido -->
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M..." fill="#FF6200"/>
        </svg>
        <span class="nav-label">Contato</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="#" style="text-decoration:none;display:flex;flex-direction:column;align-items:center;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="4" y="7" width="16" height="10" rx="2" stroke="#FF6200" stroke-width="2" fill="none"/>
          <path d="M4 7L12 13L20 7" stroke="#FF6200" stroke-width="2" fill="none"/>
        </svg>
        <span class="nav-label">Empréstimo</span>
      </a>
    </div>
  </div>
</div>

<!-- Upload buttons -->
<div id="upload-buttons">
  <button id="btn-upload-participante" class="upload-btn" title="Upload de Arquivos - Participante">
    <!-- svg -->
    <span>Upload de Arquivos - Participante</span>
  </button>

  <button id="btn-analise-documentacao" class="upload-btn" title="Análise de Documentação">
    <span>Análise de Documentação</span>
  </button>
</div>

<!-- Floating planos button -->
<div id="floating-planos-btn" style="position: fixed; right: 20px; bottom: 80px; z-index: 999;">
  <svg width="56" height="56" viewBox="0 0 24 24"><rect width="24" height="24" rx="12" fill="#F47C20"/><path d="M7.5 13.5h-1A1.5 1.5 0 0 1 5 12V7.5A1.5 1.5 0 0 1 6.5 6h7A1.5 1.5 0 0 1 15 7.5v4.5a1.5 1.5 0 0 1-1.5 1.5h-1l-2.25 2.25a.75.75 0 0 1-1.06 0L7.5 13.5Z" stroke="#fff" stroke-width="1.5"/><rect x="13" y="13" width="6" height="4" rx="1" fill="#fff"/></svg>
</div>

<!-- Planos popup -->
<div id="planos-popup" class="planos-popup hidden" style="display:none; position: fixed; right: 20px; bottom: 140px; z-index:1000; background:#fff; padding:16px; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.12);">
  <h3>Planos de Previdência</h3>
  <div class="planos-list">
    <div class="plano-card" data-plano="pleno-prev" style="display:flex;align-items:center;gap:12px;">
      <?= $this->Html->image('Plenoprev.png', ['alt' => 'Pleno Prev', 'style' => 'width:64px;height:auto;']) ?>
      <button class="modal-button">Pleno Prev</button>
    </div>
  </div>
  <button class="close-popup modal-button" style="margin-top:10px;">Fechar</button>
</div>

<!-- Descrição popup -->
<div id="descricao-popup" class="planos-popup hidden" style="display:none; position: fixed; right: 20px; bottom: 140px; z-index:1000; background:#fff; padding:16px; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.12);">
  <h3 id="plano-nome"></h3>
  <p id="plano-descricao"></p>
  <button class="close-popup modal-button" style="margin-top:10px;">Fechar</button>
</div>

<!-- Modal Upload Participante (resumido) -->
<div id="modal-upload-participante" class="modal hidden" style="display:none;">
  <div class="modal-content">
    <div class="modal-content-inner">
      <h2>🔐 Upload de Arquivos - Participante</h2>

      <div id="step-verificacao-cpf" class="upload-step">
        <div class="form-group">
          <label for="cpf-participante">Digite seu CPF para continuar:</label>
          <input type="text" id="cpf-participante" class="form-input" placeholder="000.000.000-00" maxlength="14">
          <small class="help-text">Informe seu CPF para verificar seus documentos pendentes</small>
        </div>
        <div class="form-actions">
          <button id="btn-verificar-cpf" class="modal-button btn-primary">Verificar CPF</button>
          <button id="btn-cancelar-upload" class="modal-button btn-secondary">Cancelar</button>
        </div>
        <div id="cpf-error-message" class="error-message hidden" style="display:none;"></div>
      </div>

      <!-- demais etapas mantidas front-end -->
      <div id="step-boas-vindas" class="upload-step hidden" style="display:none;">
        <div id="welcome-message" class="welcome-section"></div>
        <div class="documentos-pendentes">
          <h3>📄 Documentos para preenchimento:</h3>
          <div class="pdf-list">
            <div class="pdf-item">
              <div class="pdf-icon">📄</div>
              <div class="pdf-info">
                <span class="pdf-name">BFX - Proposta PlenoPrev - v01.pdf</span>
                <small class="pdf-description">Formulário de adesão principal</small>
              </div>
              <a href="/documentos/proposta-bfx-fixed.pdf" target="_blank" class="btn-download">Baixar</a>
            </div>
            <div class="pdf-item">
              <div class="pdf-icon">📝</div>
              <div class="pdf-info">
                <span class="pdf-name">Formulário CEPREV - (Novo).docx</span>
                <small class="pdf-description">Formulário complementar CEPREV</small>
              </div>
              <a href="/documentos/formulario-ceprev-fixed_novo.docx" target="_blank" class="btn-download">Baixar</a>
            </div>
          </div>
        </div>
        <div class="next-step">
          <p><strong>Próximo passo:</strong> Baixe, preencha e envie os documentos usando o botão abaixo.</p>
          <button id="btn-ir-para-upload" class="modal-button btn-primary">Continuar para Upload</button>
        </div>
      </div>

      <div id="step-upload-arquivos" class="upload-step hidden" style="display:none;">
        <h3>📤 Envie seus documentos preenchidos</h3>
        <div class="upload-area">
          <input type="file" id="file-input" multiple accept=".pdf" style="display:none;">
          <div id="drop-zone" class="drop-zone">
            <div class="drop-zone-content">
              <p>Arraste seus arquivos PDF aqui ou <span class="click-to-browse">clique para selecionar</span></p>
              <small>Apenas arquivos PDF são aceitos (máximo 10MB cada)</small>
            </div>
          </div>
        </div>
        <div id="file-list" class="file-list hidden" style="display:none;"></div>
        <div id="upload-progress" class="upload-progress hidden" style="display:none;">
          <div class="progress-bar"><div class="progress-fill" id="progress-fill"></div></div>
          <div class="progress-text" id="progress-text">Enviando arquivos...</div>
        </div>
        <div class="form-actions">
          <button id="btn-enviar-arquivos" class="modal-button btn-primary" disabled>Enviar Documentos</button>
          <button id="btn-voltar-documentos" class="modal-button btn-secondary">Voltar</button>
        </div>
      </div>

      <div id="step-sucesso-upload" class="upload-step hidden" style="display:none;">
        <div class="success-content">
          <div class="success-icon">✅</div>
          <h3>Upload realizado com sucesso!</h3>
          <p>Seus documentos foram enviados e estão sendo analisados pela nossa equipe.</p>
          <button id="btn-finalizar-upload" class="modal-button btn-primary">Finalizar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Admin login modal -->
<div id="modal-admin-login" class="modal hidden" style="display:none;">
  <div class="modal-content">
    <div class="modal-content-inner">
      <h2>🔒 Acesso Administrativo</h2>
      <p>Digite suas credenciais para acessar a análise de documentos:</p>
      <div class="form-group">
        <label for="admin-username">Usuário:</label>
        <input type="text" id="admin-username" class="form-input" placeholder="Digite o usuário" autocomplete="username">
      </div>
      <div class="form-group">
        <label for="admin-password">Senha:</label>
        <input type="password" id="admin-password" class="form-input" placeholder="Digite a senha" autocomplete="current-password">
      </div>
      <div id="login-error-message" class="error-message hidden" style="display:none;"></div>
      <div class="form-actions">
        <button id="btn-fazer-login" class="modal-button btn-primary">🔑 Entrar</button>
        <button id="btn-cancelar-login" class="modal-button btn-secondary">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<!-- Análise de Documentação modal -->
<div id="modal-analise-documentacao" class="modal hidden" style="display:none;">
  <div class="modal-content">
    <div class="modal-content-inner">
      <h2>📋 Análise de Documentação</h2>
      <div class="analise-header">
        <p>Lista de documentos enviados pelos participantes para análise:</p>
        <button id="btn-atualizar-lista" class="modal-button btn-secondary">🔄 Atualizar</button>
      </div>
      <div id="loading-documentos" class="loading-section">
        <div class="loading-spinner-small"></div>
        <p>Carregando documentos...</p>
      </div>
      <div id="lista-documentos" class="documentos-analise hidden" style="display:none;"></div>
      <div class="form-actions">
        <button id="btn-fechar-analise" class="modal-button btn-secondary">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Função utilitária JS in-page mínima (em caso de não ter sido carregado o arquivo js) -->
<script>
  // copia o BR code do textarea
  function copyPixCode() {
    const pix = document.getElementById('pix-code');
    if (!pix) return alert('Código Pix não disponível');
    pix.select();
    try {
      document.execCommand('copy');
      alert('Código Pix copiado para a área de transferência');
    } catch (e) {
      alert('Não foi possível copiar automaticamente. Selecione e copie manualmente.');
    }
  }

  // mostram/ocultam modais básicos — o arquivo externo que você já tem cuidará de mais comportamento
  document.addEventListener('DOMContentLoaded', function () {
    // abrir simulador
    const abrirSim = document.getElementById('abrir-simulador-modal');
    const simuladorModal = document.getElementById('simulador-modal');
    const fecharSim = document.getElementById('fechar-simulador');
    if (abrirSim && simuladorModal) {
      abrirSim.addEventListener('click', function (e) { e.preventDefault(); simuladorModal.style.display = 'block'; });
    }
    if (fecharSim && simuladorModal) {
      fecharSim.addEventListener('click', function () { simuladorModal.style.display = 'none'; });
    }

    // iniciar formulário (botão principal)
    const iniciarBtn = document.getElementById('iniciar-btn');
    const registerForm = document.getElementById('register-form');
    if (iniciarBtn && registerForm) {
      iniciarBtn.addEventListener('click', function (e) { e.preventDefault(); registerForm.style.display = 'flex'; });
    }

    // fechar success modal
    const closeModalBtn = document.getElementById('close-modal');
    const successModal = document.getElementById('success-modal');
    if (closeModalBtn && successModal) {
      closeModalBtn.addEventListener('click', function () { successModal.style.display = 'none'; registerForm.style.display = 'none'; });
    }

    // floating planos btn toggler (se script externo não rodar)
    const floatingPlanosBtn = document.getElementById('floating-planos-btn');
    const planosPopup = document.getElementById('planos-popup');
    if (floatingPlanosBtn && planosPopup) {
      floatingPlanosBtn.addEventListener('click', function () {
        planosPopup.style.display = planosPopup.style.display === 'block' ? 'none' : 'block';
      });
      document.addEventListener('click', function (event) {
        if (!planosPopup.contains(event.target) && !floatingPlanosBtn.contains(event.target)) {
          planosPopup.style.display = 'none';
        }
      });
    }
  });
</script>

<!-- Carrega o JS principal (o arquivo que você enviou) -->
<?= $this->Html->script('application') ?>
