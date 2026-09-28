<?php
/**
 * O modal "Link de retomada" inteiro (moldura + conteúdo), para reaproveitar
 * tanto na listagem (um por linha) quanto nos Detalhes (um só).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AdhesionInitialData $adhesion
 * @var array $resumeSteps
 * @var string $suggestedStep
 * @var string|null $resumeUrl
 */
?>
<div class="modal fade" id="resumeLinkModal-<?= $adhesion->id ?>" tabindex="-1" aria-labelledby="resumeLinkModalLabel-<?= $adhesion->id ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resumeLinkModalLabel-<?= $adhesion->id ?>"><i class="bi bi-link-45deg"></i> Link de retomada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <?= $this->element('../Admin/Adhesions/resume_panel', [
                    'adhesion' => $adhesion,
                    'resumeSteps' => $resumeSteps,
                    'suggestedStep' => $suggestedStep,
                    'resumeUrl' => $resumeUrl,
                ]) ?>
            </div>
        </div>
    </div>
</div>
