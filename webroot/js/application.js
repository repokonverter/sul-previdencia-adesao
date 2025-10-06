// Função principal de inicialização
function initializeApp() {
  console.log('initializeApp foi chamada');
  const iniciarBtn = document.getElementById('iniciar-btn');
  const registerForm = document.getElementById('register-form');
  const balanceCard = document.querySelector('.balance-card');
  const closeModalBtn = document.getElementById('close-modal');
  const successModal = document.getElementById('success-modal');
  const simularBtn = document.getElementById('simular-btn');
  const floatingPlanosBtn = document.getElementById('floating-planos-btn');
  const planosPopup = document.getElementById('planos-popup');
  const closePopupBtns = document.querySelectorAll('.close-popup');

  console.log('floatingPlanosBtn encontrado:', floatingPlanosBtn);
  console.log('planosPopup encontrado:', planosPopup);

  // Forçar aplicação de estilos no app-container
  const appContainer = document.querySelector('.app-container');
  if (appContainer) {
    appContainer.style.border = 'none';
    appContainer.style.borderRadius = '0';
    appContainer.style.width = '100%';
    appContainer.style.minHeight = '100vh';
  }

  // Forçar fundo no body
  document.body.style.backgroundColor = '#FF6B00';

  if (iniciarBtn) {
    iniciarBtn.addEventListener('click', () => {
      registerForm.style.display = 'flex';
      balanceCard.style.display = 'none';
    });
  }

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => {
      successModal.style.display = 'none';
      registerForm.style.display = 'none';
      balanceCard.style.display = 'flex';
    });
  }

  if (simularBtn) {
    simularBtn.addEventListener('click', () => {
      alert('Funcionalidade de simulação em desenvolvimento!');
    });
  }

  // Adiciona evento de clique no botão flutuante
  if (floatingPlanosBtn && planosPopup) {
    console.log('Adicionando evento de click ao botão flutuante');
    floatingPlanosBtn.addEventListener('click', () => {
      console.log('Botão flutuante foi clicado!');
      planosPopup.classList.toggle('hidden');
    });

    // Fecha o popup quando clicar no botão de fechar
    closePopupBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        planosPopup.classList.add('hidden');
      });
    });

    // Fecha o popup quando clicar fora dele
    document.addEventListener('click', (event) => {
      if (!planosPopup.contains(event.target) && !floatingPlanosBtn.contains(event.target)) {
        planosPopup.classList.add('hidden');
      }
    });
  } else {
    console.log('ERRO: Botão flutuante ou popup não foram encontrados no DOM');
  }
}

// Inicializa o app quando o DOM estiver pronto
if (document.readyState === 'loading') {
  console.log('DOM ainda carregando, aguardando DOMContentLoaded');
  document.addEventListener('DOMContentLoaded', initializeApp);
} else {
  console.log('DOM já carregado, iniciando app imediatamente');
  initializeApp();
}

// Suporte adicional para Turbo, se estiver presente
if (typeof Turbo !== 'undefined') {
  console.log('Turbo detectado, adicionando listener turbo:load');
  document.addEventListener("turbo:load", initializeApp);
}