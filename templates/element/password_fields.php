<?php
/**
 * Nova senha + confirmação, com os requisitos de PasswordPolicy num
 * checklist que acompanha a digitação e o botão de mostrar/ocultar. O
 * checklist só orienta: quem decide é o servidor.
 *
 * @var \App\View\AppView $this
 * @var list<string> $personalWords Já normalizadas (PasswordPolicy::personalWords()).
 */

use App\Utility\PasswordPolicy;

// Os erros vêm do servidor (UsersTable::changePassword()). Escritos à mão,
// com d-block: dentro do input-group o invalid-feedback do Bootstrap não é
// irmão do campo e ficaria escondido.
$fieldErrors = function (string $field): string {
    $messages = $this->Form->context()->error($field);

    return $messages === []
        ? ''
        : '<div class="invalid-feedback d-block">' . implode('<br>', array_map('h', $messages)) . '</div>';
};

$rules = [
    'min' => __('Pelo menos {0} caracteres', PasswordPolicy::MIN_LENGTH),
    'max' => __('No máximo {0} caracteres', PasswordPolicy::MAX_LENGTH),
    'service' => __('Sem o nome da Sul Previdência'),
    'personal' => __('Sem o seu nome ou e-mail'),
    'confirm' => __('A confirmação é igual à senha'),
];
?>
<div class="js-password-fields"
    data-min="<?= PasswordPolicy::MIN_LENGTH ?>"
    data-max="<?= PasswordPolicy::MAX_LENGTH ?>"
    data-service-words="<?= h(json_encode(PasswordPolicy::SERVICE_WORDS)) ?>"
    data-personal-words="<?= h(json_encode($personalWords)) ?>">

    <div class="mb-3">
        <label class="form-label" for="new-password"><?= __('Nova senha') ?></label>
        <div class="input-group">
            <?= $this->Form->password('new_password', [
                'id' => 'new-password',
                'class' => 'form-control js-password-new',
                'autocomplete' => 'new-password',
                'required' => true,
                'value' => '',
            ]) ?>
            <button type="button" class="btn btn-outline-secondary js-password-toggle" title="<?= __('Mostrar senha') ?>" aria-label="<?= __('Mostrar senha') ?>">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <?= $fieldErrors('new_password') ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="confirm-password"><?= __('Confirme a nova senha') ?></label>
        <?= $this->Form->password('confirm_password', [
            'id' => 'confirm-password',
            'class' => 'form-control js-password-confirm',
            'autocomplete' => 'new-password',
            'required' => true,
            'value' => '',
        ]) ?>
        <?= $fieldErrors('confirm_password') ?>
    </div>

    <ul class="list-unstyled small mb-3">
        <?php foreach ($rules as $rule => $label): ?>
            <li class="text-muted" data-rule="<?= $rule ?>">
                <i class="bi bi-circle me-1"></i><?= h($label) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<script>
document.querySelectorAll('.js-password-fields').forEach((box) => {
    const input = box.querySelector('.js-password-new');
    const confirm = box.querySelector('.js-password-confirm');
    const toggle = box.querySelector('.js-password-toggle');
    const min = Number(box.dataset.min);
    const max = Number(box.dataset.max);
    const serviceWords = JSON.parse(box.dataset.serviceWords);
    const personalWords = JSON.parse(box.dataset.personalWords);

    // Mesma normalização de PasswordPolicy::normalize().
    const normalize = (value) => value.toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]/g, '');
    const containsAny = (value, words) => words.some((word) => word && value.includes(word));
    const length = (value) => Array.from(value).length;

    const mark = (rule, ok) => {
        const item = box.querySelector('[data-rule="' + rule + '"]');
        item.classList.toggle('text-success', ok);
        item.classList.toggle('text-muted', !ok);
        item.querySelector('i').className = 'bi me-1 ' + (ok ? 'bi-check-circle-fill' : 'bi-circle');
    };

    const update = () => {
        const value = input.value;
        const normalized = normalize(value);

        mark('min', length(value) >= min);
        mark('max', value !== '' && length(value) <= max);
        mark('service', value !== '' && !containsAny(normalized, serviceWords));
        mark('personal', value !== '' && !containsAny(normalized, personalWords));
        mark('confirm', value !== '' && confirm.value === value);
    };

    input.addEventListener('input', update);
    confirm.addEventListener('input', update);

    toggle.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = confirm.type = show ? 'text' : 'password';
        toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        toggle.title = show ? 'Ocultar senha' : 'Mostrar senha';
        toggle.setAttribute('aria-label', toggle.title);
    });

    update();
});
</script>
