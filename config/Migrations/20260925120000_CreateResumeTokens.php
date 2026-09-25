<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * O link que devolve uma proposta ao proponente.
 *
 * Token aleatório, e não hash do id: um valor derivado do id torna a base
 * enumerável se o algoritmo vazar, e com ids sequenciais é trivial gerar
 * candidatos. Gerado no servidor, pelo mesmo motivo de storage_uuid — o
 * navegador não escolhe o segredo que o autoriza.
 *
 * Separado de storage_uuid de propósito, ainda que os dois abram a mesma
 * adesão: um é a página de pagamento e o outro é o formulário inteiro, com
 * CPF, filiação, conta bancária e declaração de saúde. Um segredo só para os
 * dois significaria não poder revogar um sem matar o outro.
 */
class CreateResumeTokens extends BaseMigration
{
    public function change(): void
    {
        $this->table('adhesion_initial_data')
            ->addColumn('resume_token', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('resume_token_expires_at', 'datetime', ['null' => true])
            // A etapa em que o proponente recomeça, escolhida pelo admin ao
            // gerar o link. Guarda o id do passo, e não a posição: um número
            // passaria a apontar para outro passo no próximo reordenamento, e
            // o passo da saúde nem sempre existe.
            ->addColumn('resume_step', 'string', ['limit' => 30, 'null' => true])
            ->addIndex(['resume_token'], ['unique' => true, 'name' => 'adhesion_initial_data_resume_token_unique'])
            ->update();

        // Só agora, que existe quem o leia. Um parâmetro configurável que não
        // controla nada só confunde quem abre a tela no meio do caminho.
        $this->table('plan_parameters')
            ->addColumn('resume_link_days', 'integer', [
                'null' => false,
                'default' => 7,
                'comment' => 'Validade, em dias, do link de retomada da proposta',
            ])
            ->update();
    }
}
