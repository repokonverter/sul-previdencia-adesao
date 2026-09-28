<?php
/**
 * Conteúdo do modal "Link de retomada". Compartilhado entre a listagem (um
 * modal por linha) e os Detalhes (um só) -- por isso todo id aqui carrega o
 * id da adesão como sufixo, senão duas instâncias na mesma página colidiriam.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AdhesionInitialData $adhesion
 * @var array $resumeSteps
 * @var string $suggestedStep
 * @var string|null $resumeUrl
 */
?>
<?php if ($resumeUrl !== null): ?>
    <div class="alert <?= $adhesion->resumeTokenHasExpired() ? 'alert-warning' : 'alert-success' ?>">
        <?php if ($adhesion->resumeTokenHasExpired()): ?>
            <i class="bi bi-clock-history"></i> Este link <strong>expirou</strong>
            em <?= $adhesion->resume_token_expires_at->format('d/m/Y H:i') ?>.
            Gere outro abaixo.
        <?php else: ?>
            <i class="bi bi-check-circle"></i> Link ativo, válido até
            <strong><?= $adhesion->resume_token_expires_at->format('d/m/Y H:i') ?></strong>,
            apontando para
            <strong><?= h(\App\Services\AdhesionSteps::ORDER[$adhesion->resume_step] ?? 'a primeira etapa') ?></strong>.
        <?php endif; ?>
    </div>

    <div class="input-group mb-3">
        <input type="text" class="form-control resume-url-field" id="resumeUrl-<?= $adhesion->id ?>" value="<?= h($resumeUrl) ?>" readonly>
        <button class="btn btn-outline-secondary copy-resume-url" type="button">
            <i class="bi bi-clipboard"></i> Copiar
        </button>
        <?php
        $phone = preg_replace('/\D/', '', (string)$adhesion->phone);
        $message = 'Olá! Continue sua proposta de adesão por aqui: ' . $resumeUrl;
        ?>
        <?php if ($phone !== ''): ?>
            <a class="btn btn-outline-success"
               target="_blank"
               rel="noopener"
               href="https://wa.me/55<?= h($phone) ?>?text=<?= rawurlencode($message) ?>">
                <i class="bi bi-whatsapp"></i> WhatsApp
            </a>
        <?php endif; ?>
    </div>

    <?= $this->Form->create(null, [
        'url' => ['action' => 'sendResumeLink', $adhesion->id],
        'class' => 'row g-2 align-items-end mb-3',
    ]) ?>
    <div class="col-md-6">
        <label for="resumeEmail-<?= $adhesion->id ?>" class="form-label">Enviar por e-mail para</label>
        <input type="email" name="email" id="resumeEmail-<?= $adhesion->id ?>" class="form-control"
               value="<?= h($adhesion->email) ?>" required>
        <div class="form-text">
            Vem preenchido com o e-mail da adesão. Digitar outro envia para ele
            sem alterar o cadastro.
        </div>
    </div>
    <div class="col-md-3">
        <?= $this->Form->button('<i class="bi bi-envelope"></i> Enviar', [
            'escapeTitle' => false,
            'class' => 'btn btn-outline-primary w-100',
        ]) ?>
    </div>
    <?= $this->Form->end() ?>

    <?= $this->Form->postLink(
        '<i class="bi bi-x-circle"></i> Revogar link',
        ['action' => 'revokeResumeLink', $adhesion->id],
        [
            'escape' => false,
            'class' => 'btn btn-outline-danger btn-sm',
            'confirm' => 'Revogar o link? Quem o tiver deixa de conseguir abrir a proposta.',
        ]
    ) ?>
<?php else: ?>
    <p class="text-muted">
        Nenhum link ativo. Gere um para o proponente continuar de onde parou.
    </p>
<?php endif; ?>

<hr class="my-4">

<h6 class="fw-bold mb-2"><?= $resumeUrl === null ? 'Gerar link' : 'Gerar um link novo' ?></h6>
<p class="text-muted small">
    Gerar invalida o link anterior. O mesmo link pode ser distribuído por
    quantos canais quiser.
</p>

<?= $this->Form->create(null, ['url' => ['action' => 'issueResumeLink', $adhesion->id]]) ?>
<div class="row align-items-end">
    <div class="col-md-6">
        <label for="resumeStep-<?= $adhesion->id ?>" class="form-label">O proponente recomeça em</label>
        <select name="step" id="resumeStep-<?= $adhesion->id ?>" class="form-select">
            <?php foreach ($resumeSteps as $id => $step): ?>
                <option value="<?= h($id) ?>"
                    <?= $step['selectable'] ? '' : 'disabled' ?>
                    <?= $id === $suggestedStep ? 'selected' : '' ?>>
                    <?= h($step['label']) ?><?= $step['complete'] ? '' : ' — em branco' ?><?= $step['reason'] ? ' (' . h($step['reason']) . ')' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">
            Etapas que dependem de outra ainda em branco ficam indisponíveis: o
            proponente as pularia sem preencher, e a adesão seria finalizada
            incompleta.
        </div>
    </div>
    <div class="col-md-3">
        <?= $this->Form->button('<i class="bi bi-link-45deg"></i> Gerar link', [
            'escapeTitle' => false,
            'class' => 'btn btn-primary w-100',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>
