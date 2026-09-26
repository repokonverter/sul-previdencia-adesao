<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Base das tabelas do projeto.
 *
 * Existe por um motivo só, mas um que já custou caro: o carimbo de tempo.
 * As migrations usam `addTimestamps()` do Phinx, que cria as colunas `created`
 * e `updated`; o behavior Timestamp do CakePHP, por padrão, grava `created` e
 * `modified`. Como `modified` não existe na maior parte das tabelas daqui, o
 * valor era atribuído a uma propriedade que o save descarta — e `updated`
 * ficou NULL em todas elas, sem erro nenhum para denunciar.
 *
 * Os três nomes são declarados de uma vez, em vez de consultados no schema,
 * porque initialize() da base roda antes de a subclasse chamar setTable(): ler
 * o schema aqui consultaria o nome convencional, que nem sempre é o nome real
 * (AdhesionInitialDatas aponta para `adhesion_initial_data`). Declarar os três
 * não tem custo: o save monta a query a partir das colunas do schema, então a
 * atribuição a um nome que a tabela não tem morre em memória.
 */
abstract class AppTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                    // `updated` é o que as migrations criam; `modified` é o que
                    // `users` trouxe do bake. Vale para quem tiver a coluna.
                    'modified' => 'always',
                    'updated' => 'always',
                ],
            ],
        ]);
    }
}
