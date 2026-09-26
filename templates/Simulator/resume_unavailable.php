<?php
/**
 * @var \App\View\AppView $this
 * @var bool $expired
 */
$this->assign('title', 'Proposta');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card p-4 shadow-sm text-center">
                <h3 class="fw-bold text-primary mb-3">
                    <?= $expired ? 'Este link expirou' : 'Link não encontrado' ?>
                </h3>

                <p class="mb-3">
                    <?php if ($expired): ?>
                        O prazo para continuar esta proposta pelo link terminou.
                        <strong>Seus dados continuam guardados</strong> — fale com seu
                        atendente e ele envia um link novo.
                    <?php else: ?>
                        Este endereço não corresponde a nenhuma proposta. Verifique se o
                        link foi copiado por inteiro, ou fale com seu atendente.
                    <?php endif; ?>
                </p>

                <div>
                    <?= $this->Html->link(
                        'Ir para a página inicial',
                        ['controller' => 'Pages', 'action' => 'display', 'home'],
                        ['class' => 'btn btn-primary']
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>
