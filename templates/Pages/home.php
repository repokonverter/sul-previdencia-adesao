<?php

/**
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Sul Previdência');

echo $this->Html->css('application');
?>

<style>
    body {
        background: #fff !important;
        font-family: 'Inter', Arial, sans-serif;
        margin: 0;
        color: #222;
    }

    .app-container {
        width: 100%;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        position: relative;
        background-color: var(--primary-color) !important;
        overflow: hidden;
    }

    .fintech-hero {
        max-width: 700px;
        margin: 120px auto 0 8vw;
        text-align: left;
        padding: 0 24px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        min-height: 60vh;
        background: #fff;
    }

    .fintech-hero-left {
        align-items: flex-start;
        justify-content: flex-start;
    }

    .fintech-hero h1 {
        font-size: 3rem;
        font-weight: 400;
        margin-bottom: 16px;
        line-height: 1.1;
    }

    .fintech-hero .laranja {
        color: #F47C20 !important;
        font-weight: 700;
        opacity: 1 !important;
    }

    .fintech-subtitle {
        font-size: 1.3rem;
        color: #F47C20 !important;
        margin-bottom: 32px;
    }

    .fintech-botoes {
        display: flex;
        gap: 24px;
        margin-top: 16px;
    }

    .fintech-btn-lg,
    .btn-laranja {
        font-size: 1.2rem;
        padding: 18px 48px;
        display: inline-block;
        background: #F47C20 !important;
        color: #fff !important;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(32, 68, 110, 0.08);
        transition: background 0.2s, color 0.2s;
    }

    .fintech-btn-lg:hover,
    .btn-laranja:hover {
        background: #e55d00 !important;
        color: #fff !important;
    }

    .fintech-btn-lg:active,
    .btn-laranja:active {
        background: #b34700 !important;
        color: #fff !important;
    }

    @media (max-width: 900px) {
        .fintech-hero {
            margin: 60px 0 0 0;
            padding: 0 8vw;
            min-height: 40vh;
        }

        .fintech-botoes {
            flex-direction: column;
            gap: 16px;
            width: 100%;
        }

        .fintech-btn-lg,
        .btn-laranja {
            width: 100%;
            text-align: center;
        }
    }

    /* Estilo base para botões */
    .function-card,
    .submit-button,
    .modal-button {
        transition: transform 0.2s ease, background-color 0.3s ease;
    }

    /* Animação ao clicar nos botões Iniciar e Simular */
    .function-card:active {
        transform: scale(1.05);
    }

    /* Animação ao clicar no botão Enviar cadastro */
    .submit-button {
        background-color: #FF6200;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px;
        cursor: pointer;
    }

    .submit-button:hover {
        background-color: #E55D00;
    }

    .submit-button:active {
        transform: scale(0.95);
    }

    /* Estilo e animação do formulário de cadastro */
    .register-screen {
        display: none;
        flex-direction: column;
        background-color: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        width: 90%;
        max-width: 600px;
        margin: 20px auto;
    }

    .register-screen.show {
        display: flex;
        animation: fadeInForm 0.3s ease forwards;
    }

    .register-screen.hide {
        animation: fadeOutForm 0.3s ease forwards;
    }

    /* Ajuste nos campos do formulário */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-size: 16px;
        color: #333;
        margin-bottom: 8px;
        display: block;
    }

    .form-input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
    }

    .register-title {
        font-size: 24px;
        color: #FF6200;
        margin-bottom: 20px;
        text-align: center;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        color: #333;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
    }

    /* Keyframes para animação de fade-in e fade-out */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }

        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }

    @keyframes fadeInForm {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeOutForm {
        from {
            opacity: 1;
            transform: translateY(0);
        }

        to {
            opacity: 0;
            transform: translateY(20px);
        }
    }

    .tela-inicial-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
    }

    .card-central {
        text-align: center;
        background: #fff;
        padding: 40px 32px;
        border-radius: 16px;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
    }

    .laranja {
        color: #F47C20;
        font-weight: bold;
    }

    .botoes {
        margin-top: 24px;
        display: flex;
        gap: 16px;
    }

    /* Estilos dos botões de upload */
    #upload-buttons {
        position: fixed;
        bottom: 20px;
        right: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 995;
    }

    .upload-btn {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 12px;
        color: #666;
        transition: all 0.2s ease;
        backdrop-filter: blur(5px);
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .upload-btn:hover {
        background: rgba(244, 124, 32, 0.95);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(244, 124, 32, 0.3);
    }

    .upload-btn:hover svg {
        stroke: white;
    }

    .upload-btn svg {
        transition: stroke 0.2s ease;
    }

    @media (max-width: 768px) {
        #upload-buttons {
            bottom: 10px;
            right: 10px;
        }

        .upload-btn {
            font-size: 10px;
            padding: 6px 8px;
        }

        .upload-btn svg {
            width: 16px;
            height: 16px;
        }
    }

    /* Estilos do botão flutuante e pop-up de planos */
    #floating-planos-btn {
        position: fixed;
        bottom: 80px;
        right: 20px;
        cursor: pointer;
        z-index: 999;
        background: #fff;
        border-radius: 50%;
        padding: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        transition: transform 0.2s ease;
    }

    #floating-planos-btn:hover {
        transform: scale(1.1);
    }

    .planos-popup {
        position: fixed;
        bottom: 140px;
        right: 20px;
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 998;
        width: 300px;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .planos-popup.hidden {
        opacity: 0;
        transform: translateY(20px);
        pointer-events: none;
    }

    .planos-popup h3 {
        color: #333;
        margin: 0 0 15px 0;
        font-size: 18px;
    }

    .planos-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .plano-card {
        background: #f5f5f5;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .plano-card:hover {
        background: #eeeeee;
    }

    /* Loading Overlay Styles */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
        transition: opacity 0.3s ease;
    }

    .loading-overlay.hidden {
        opacity: 0;
        pointer-events: none;
    }

    .loading-content {
        background: white;
        border-radius: 16px;
        padding: 40px 30px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .loading-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #FF6200;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .loading-title {
        color: #333;
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 12px;
    }

    .loading-message {
        color: #666;
        font-size: 16px;
        line-height: 1.5;
        margin-bottom: 30px;
    }

    .loading-steps {
        display: flex;
        flex-direction: column;
        gap: 12px;
        text-align: left;
    }

    .step {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: 8px;
        background: #f8f9fa;
        opacity: 0.5;
        transition: all 0.3s ease;
    }

    .step.active {
        background: #e8f5e8;
        opacity: 1;
        transform: scale(1.02);
    }

    .step.completed {
        background: #d4edda;
        opacity: 1;
    }

    .step-icon {
        font-size: 20px;
    }

    .step-text {
        color: #333;
        font-weight: 500;
    }

    .step.active .step-text {
        color: #28a745;
    }

    .step.completed .step-text {
        color: #155724;
    }

    /* Animação para progresso dos steps */
    @keyframes stepProgress {
        0% {
            transform: translateX(-20px);
            opacity: 0;
        }

        100% {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .step.activating {
        animation: stepProgress 0.5s ease forwards;
    }

    .plano-card img {
        width: 50px;
        height: 50px;
        object-fit: contain;
    }

    .plano-card button {
        background: #FF6200;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .plano-card button:hover {
        background: #e55d00;
    }

    .close-popup {
        background: none;
        border: none;
        color: #666;
        padding: 8px;
        margin-top: 15px;
        cursor: pointer;
        width: 100%;
        text-align: center;
        font-size: 14px;
    }

    .close-popup:hover {
        color: #333;
    }

    @media (max-width: 768px) {
        #floating-planos-btn {
            bottom: 100px;
        }

        .planos-popup {
            bottom: 160px;
            right: 10px;
            left: 10px;
            width: auto;
        }
    }

    .hidden {
        display: none;
    }

    /* Estilos para modais de upload */
    .modal.hidden {
        display: none;
    }

    .upload-step {
        animation: fadeInStep 0.3s ease forwards;
    }

    .upload-step.hidden {
        display: none;
    }

    @keyframes fadeInStep {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .help-text {
        color: #666;
        font-size: 14px;
        margin-top: 5px;
        display: block;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        justify-content: flex-end;
    }

    .btn-primary {
        background: #F47C20 !important;
        color: white !important;
    }

    .btn-primary:hover {
        background: #e55d00 !important;
    }

    .btn-primary:disabled {
        background: #ccc !important;
        cursor: not-allowed;
    }

    .btn-secondary {
        background: #6c757d !important;
        color: white !important;
    }

    .btn-secondary:hover {
        background: #5a6268 !important;
    }

    .error-message {
        color: #dc3545;
        font-size: 14px;
        margin-top: 10px;
        padding: 10px;
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 4px;
    }

    .error-message.hidden {
        display: none;
    }

    .welcome-section {
        background: #e8f5e8;
        border: 1px solid #c3e6c3;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 20px;
    }

    .documentos-pendentes {
        margin: 20px 0;
    }

    .pdf-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 15px;
    }

    .pdf-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }

    .pdf-icon {
        font-size: 24px;
    }

    .pdf-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .pdf-name {
        font-weight: 600;
        color: #333;
    }

    .pdf-description {
        color: #666;
        font-size: 12px;
    }

    .btn-download {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        background: #F47C20;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 14px;
        transition: background 0.2s ease;
    }

    .btn-download:hover {
        background: #e55d00;
        color: white;
        text-decoration: none;
    }

    .next-step {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 16px;
        margin-top: 20px;
    }

    .upload-area {
        margin: 20px 0;
    }

    .drop-zone {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fafafa;
    }

    .drop-zone:hover {
        border-color: #F47C20;
        background: #fff;
    }

    .drop-zone.dragover {
        border-color: #F47C20;
        background: #fff5f0;
    }

    .drop-zone-content svg {
        margin-bottom: 16px;
    }

    .click-to-browse {
        color: #F47C20;
        font-weight: 600;
        cursor: pointer;
    }

    .file-list {
        margin: 20px 0;
        max-height: 200px;
        overflow-y: auto;
    }

    .file-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 8px;
    }

    .file-item .file-name {
        flex: 1;
        font-size: 14px;
    }

    .file-item .file-size {
        font-size: 12px;
        color: #666;
    }

    .file-item .remove-file {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        font-size: 12px;
    }

    .upload-progress {
        margin: 20px 0;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: #F47C20;
        transition: width 0.3s ease;
        width: 0%;
    }

    .progress-text {
        text-align: center;
        margin-top: 8px;
        font-size: 14px;
        color: #666;
    }

    .success-content {
        text-align: center;
        padding: 20px;
    }

    .success-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }

    .analise-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .loading-section {
        text-align: center;
        padding: 40px 20px;
    }

    .loading-spinner-small {
        width: 30px;
        height: 30px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #F47C20;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }

    .documentos-analise {
        max-height: 400px;
        overflow-y: auto;
    }

    .documento-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 8px;
        background: #fff;
    }

    .documento-info {
        flex: 1;
    }

    .documento-nome {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }

    .documento-participante {
        color: #666;
        font-size: 14px;
        margin-bottom: 2px;
    }

    .documento-detalhes {
        color: #888;
        font-size: 12px;
    }

    .documento-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .documento-status.pendente {
        background: #fff3cd;
        color: #856404;
    }

    .documento-status.aprovado {
        background: #d4edda;
        color: #155724;
    }

    .documento-status.rejeitado {
        background: #f8d7da;
        color: #721c24;
    }

    .documento-acoes {
        display: flex;
        gap: 8px;
    }

    .btn-acao {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .btn-download-doc {
        background: #17a2b8;
        color: white;
    }

    .btn-download-doc:hover {
        background: #138496;
    }

    .btn-aprovar {
        background: #28a745;
        color: white;
    }

    .btn-aprovar:hover {
        background: #218838;
    }

    .btn-rejeitar {
        background: #dc3545;
        color: white;
    }

    .btn-rejeitar:hover {
        background: #c82333;
    }

    /* Estilos específicos para o modal de login administrativo */
    #modal-admin-login .modal-content {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px solid #F47C20;
        box-shadow: 0 20px 40px rgba(244, 124, 32, 0.3);
    }

    #modal-admin-login h2 {
        color: #F47C20;
        text-align: center;
        margin-bottom: 10px;
        font-size: 24px;
    }

    #modal-admin-login p {
        text-align: center;
        color: #666;
        margin-bottom: 25px;
    }

    #modal-admin-login .form-input {
        border: 2px solid #dee2e6;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    #modal-admin-login .form-input:focus {
        border-color: #F47C20;
        box-shadow: 0 0 0 3px rgba(244, 124, 32, 0.1);
        outline: none;
    }

    #modal-admin-login .btn-primary {
        background: linear-gradient(135deg, #F47C20 0%, #e55d00 100%);
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    #modal-admin-login .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(244, 124, 32, 0.4);
    }

    #modal-admin-login .btn-primary:disabled {
        background: #6c757d;
        transform: none;
        box-shadow: none;
    }

    .btn-branco {
        background: #fff;
        color: #F47C20;
        border: 2px solid #F47C20;
        padding: 12px 28px;
        border-radius: 8px;
        font-size: 1rem;
        cursor: pointer;
        font-weight: 600;
    }

    .fintech-logo-right {
        height: 100px;
        position: absolute;
        top: 16px;
        right: 48px;
        z-index: 10;
    }

    .fintech-header {
        position: relative;
    }

    .app-nav {
        background: #fff !important;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 140px;
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 8px 0;
    }

    @media (max-width: 768px) {
        .app-nav {
            background: #fff !important;
            padding: 12px 5%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 90px;
            border-top: 1px solid #f0f0f0;
        }
    }

    #floating-planos-btn {
        position: fixed;
        bottom: 90px;
        right: 30px;
        background: #F47C20;
        border-radius: 50%;
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 1000;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        transition: background 0.2s;
    }

    #floating-planos-btn svg rect {
        fill: #F47C20 !important;
    }

    #floating-planos-btn svg rect[x="14"] {
        fill: #F47C20 !important;
    }

    #floating-planos-btn:hover {
        background: #e55d00;
    }

    .planos-popup {
        position: fixed;
        bottom: 110px;
        right: 30px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.2);
        padding: 24px;
        z-index: 1001;
        min-width: 320px;
        max-width: 90vw;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .planos-popup.hidden {
        display: none;
    }

    .planos-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin: 16px 0;
    }

    .plano-card {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .plano-card img {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        background: #eee;
    }

    .plano-card button {
        background: #F47C20;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        cursor: pointer;
        font-weight: 600;
    }

    .close-popup {
        margin-top: 12px;
        background: #ff7700;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        cursor: pointer;
        font-weight: 600;
    }
</style>

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
                <a href="#" class="fintech-btn fintech-btn-lg btn-laranja" data-bs-toggle="modal" data-bs-target="#simulatorModal">Simular minha previdência</a>
                <a href="#" class="fintech-btn fintech-btn-lg btn-laranja hidden" id="signUpButton">Faça sua adesão</a>
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
    <div id="loading-overlay" class="loading-overlay hidden">
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
    <div id="cpf-confirmation-modal" class="loading-overlay hidden">
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
    <div class="success-modal hidden" id="success-modal">
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
    <div class="modal fade" id="simulatorModal" tabindex="-1" aria-labelledby="simulatorModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="simulatorModalLabel">Simulador de previdência</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3"><strong>Para iniciar uma simulação, digite os dados abaixo:</strong></p>
                    <form id="simulatorForm">
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="dateBirth" class="form-label">Data de nascimento*</label>
                                    <input type="date" max="9999-12-31" class="form-control" name="dateBirth" placeholder="XX/XX/XXXX" required>
                                    <div class="invalid-feedback">
                                        Preenchimento obrigatório.
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="monthlyInvestment" class="form-label">Investimento mensal*</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="text" class="form-control money" name="monthlyInvestment" placeholder="Investimento mensal" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="simulate();">Simular</button>
                </div>
            </div>
        </div>
    </div>

    <!-- App Navigation -->
    <div class="app-nav">
        <div class="nav-item">
            <a href="https://www.instagram.com/sulprevidencia/" target="_blank" rel="noopener" style="text-decoration:none;display:flex;flex-direction:column;align-items:center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="#FF6200" stroke-width="2" fill="none" />
                    <circle cx="12" cy="12" r="4" stroke="#FF6200" stroke-width="2" fill="none" />
                    <circle cx="17" cy="7" r="1.5" fill="#FF6200" />
                </svg>
                <span class="nav-label">Sobre</span>
            </a>
        </div>

        <div class="nav-item">
            <a href="https://wa.me/5548991321918" target="_blank" rel="noopener" style="text-decoration:none;display:flex;flex-direction:column;align-items:center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20ZM16.2 15.2L14.7 14.7C14.53 14.65 14.36 14.7 14.23 14.82C13.68 15.32 12.89 15.32 12.34 14.82L11.18 13.66C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15L14.64 14.65C14.47 14.6 14.3 14.65 14.18 14.77C13.68 15.27 12.89 15.27 12.34 14.77L11.18 13.61C10.68 13.11 10.68 12.32 11.18 11.77C11.3 11.64 11.35 11.47 11.3 11.3L10.8 9.8C10.7 9.53 10.41 9.41 10.16 9.5C9.09 9.87 8.13 10.83 7.76 11.9C7.37 13.01 7.7 14.25 8.59 15.14C9.48 16.03 10.72 16.36 11.83 15.97C12.9 15.6 13.86 14.64 14.23 13.57C14.32 13.32 14.2 13.03 13.93 12.93L12.43 12.43C12.26 12.38 12.09 12.43 11.97 12.55C11.47 13.05 10.68 13.05 10.13 12.55L8.97 11.39C8.47 10.89 8.47 10.1 8.97 9.55C9.09 9.42 9.26 9.37 9.43 9.42L10.93 9.92C11.1 9.97 11.27 9.92 11.39 9.8C11.89 9.3 12.68 9.3 13.23 9.8L14.39 10.96C14.89 11.46 14.89 12.25 14.39 12.8C14.27 12.93 14.22 13.1 14.27 13.27L14.77 14.77C14.87 15.04 15.16 15.16 15.41 15.07C16.48 14.7 17.44 13.74 17.81 12.67C18.2 11.56 17.87 10.32 16.98 9.43C16.09 8.54 14.85 8.21 13.74 8.6C12.67 8.97 11.71 9.93 11.34 11C11.25 11.25 11.37 11.54 11.64 11.64L13.14 12.14C13.31 12.19 13.48 12.14 13.6 12.02C14.1 11.52 14.89 11.52 15.44 12.02L16.6 13.18C17.1 13.68 17.1 14.47 16.6 15.02C16.48 15.15 16.31 15.2 16.14 15.15Z" fill="#FF6200" />
                    </svg>
                </svg>
                <span class="nav-label">Contato</span>
            </a>
        </div>

        <div class="nav-item" style="display: none;">
            <a href="#" style="text-decoration:none;display:flex;flex-direction:column;align-items:center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="4" y="7" width="16" height="10" rx="2" stroke="#FF6200" stroke-width="2" fill="none" />
                    <path d="M4 7L12 13L20 7" stroke="#FF6200" stroke-width="2" fill="none" />
                </svg>
                <span class="nav-label">Empréstimo</span>
            </a>
        </div>
    </div>
</div>

<script>
    const simulate = () => {
        let isValid = true;
        const date = $('#simulatorModal input[name="dateBirth').val();
        const value = $('#simulatorModal input[name="monthlyInvestment').val().replace('.', '').replace(',', '.');
        const simulatorUrl = `<?= $this->Url->build(['controller' => 'Simulator', 'action' => 'index']); ?>?date=${date}&value=${value}`;
        const form = document.querySelectorAll(`#simulatorForm input`);

        form.forEach((input) => {
            if (!input.checkValidity())
                isValid = false;
        })

        if (!isValid) {
            $(`#simulatorForm`)[0].classList.add('was-validated')
            return;
        }

        window.location.href = simulatorUrl;
    }
</script>
