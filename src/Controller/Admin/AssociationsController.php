<?php

declare(strict_types=1);

namespace App\Controller\Admin;

/**
 * Tela "Vínculos associativos" do admin: mesmo CRUD de PartnersController,
 * restrito aos parceiros com is_association = true, reaproveitando os
 * templates de Admin/Partners (só o texto muda, via entityLabel()).
 *
 * Ver Partner::$is_association e a pergunta "Possuí vínculo associativo?" no
 * formulário público de adesão.
 */
class AssociationsController extends PartnersController
{
    protected bool $isAssociationScope = true;

    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setTemplatePath('Admin/Partners');
    }
}
