<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Trilha de auditoria das edições feitas no admin.
 *
 * Tabela dedicada em vez de reaproveitar integration_logs: uma adesão
 * finalizada gera 15-20 linhas lá, cada uma com request/response completos de
 * Clicksign e Sicoob. A pergunta "quem baixou a contribuição desta pessoa de
 * R$ 300 para R$ 250, e quando?" é de negócio, baixo volume, e alguém vai
 * fazê-la sob pressão — enterrada entre dumps de JSON de API, fica
 * inencontrável.
 *
 * Audita toda edição, não só o plano: AdhesionsController::edit() já permite
 * reescrever CPF, data de nascimento, conta bancária e beneficiários, e essas
 * são perguntas mais graves que a do valor.
 */
class CreateAdhesionAudits extends BaseMigration
{
    public function change(): void
    {
        $this->table('adhesion_audits')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            // RESTRICT, e não SET NULL: uma trilha de auditoria que perde o
            // autor deixa de ser trilha. user_name é o snapshot para o caso de
            // o usuário ser renomeado depois, mesmo padrão de broker_name e
            // promotional_code na própria adesão.
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('user_name', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('action', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('changes', 'text', ['null' => true, 'comment' => 'JSON: campo => [antes, depois]'])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addIndex(['adhesion_initial_data_id', 'created'])
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'RESTRICT'])
            ->create();

        // Estado corrente do ajuste manual, à parte do histórico: a flag trava
        // o passo do Plano e a data de nascimento para o cliente (o formulário
        // público não pode desfazer uma negociação feita por telefone), e ter
        // "quem foi o último" numa coluna evita varrer o histórico só para
        // desenhar a tela.
        $this->table('adhesion_plans')
            ->addColumn('admin_overridden', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('admin_overridden_by_user_id', 'integer', ['null' => true])
            ->addColumn('admin_overridden_at', 'datetime', ['null' => true])
            ->addForeignKey('admin_overridden_by_user_id', 'users', 'id', ['delete' => 'RESTRICT'])
            ->update();
    }
}
