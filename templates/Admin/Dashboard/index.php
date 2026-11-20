<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, array{name: string, counts: array{total: int, completed: int, signed: int, paid: int, awaitingPayment: int}}> $summaries
 */
?>
<h3>Bem-vindo ao painel administrativo!</h3>
<p>Você está autenticado como <strong><?= h($this->Identity->get('name')) ?></strong>.</p>

<div class="row g-4 mt-2">
    <?php foreach ($summaries as $slug => $summary): ?>
        <?php
        $items = [
            ['label' => 'Total', 'value' => $summary['counts']['total'], 'class' => 'text-primary'],
            ['label' => 'Concluídas', 'value' => $summary['counts']['completed'], 'class' => 'text-body'],
            ['label' => 'Assinadas', 'value' => $summary['counts']['signed'], 'class' => 'text-success'],
            ['label' => 'Pagas', 'value' => $summary['counts']['paid'], 'class' => 'text-success'],
            ['label' => 'Aguardando pagamento', 'value' => $summary['counts']['awaitingPayment'], 'class' => 'text-warning'],
        ];
        ?>
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0">Adesões &mdash; <?= h($summary['name']) ?></h6>
                        <?= $this->Html->link(
                            'Ver adesões <i class="bi bi-arrow-right"></i>',
                            ['controller' => 'Adhesions', 'action' => 'index', '?' => ['benefitPlan' => $slug]],
                            ['escape' => false, 'class' => 'btn btn-sm btn-outline-primary']
                        ) ?>
                    </div>
                    <div class="row row-cols-2 row-cols-md-5 g-3 text-center">
                        <?php foreach ($items as $item): ?>
                            <div class="col">
                                <div class="border rounded py-2 h-100">
                                    <div class="fs-3 fw-bold <?= $item['class'] ?>"><?= $this->Number->format($item['value']) ?></div>
                                    <div class="text-muted small"><?= h($item['label']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
