<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Torna adhesion_initial_data.storage_uuid único.
 *
 * O valor era gerado pelo navegador e guardado no localStorage, mas
 * initialDataId é variável JS e volta a null a cada recarregamento da página:
 * quem recarregava e começava outra adesão criava uma linha nova com o mesmo
 * uuid. Sem índice, as duplicatas conviviam em silêncio, e
 * PaymentsController::view() as resolve com ->first() — qual adesão o link
 * /pagamento/{storageUuid} abre passava a ser indeterminado, com risco de
 * alguém pagar a cobrança de outra pessoa.
 *
 * A partir daqui o uuid nasce no servidor (ver RegistrationsController::save()),
 * e o índice abaixo é o que garante que o link de pagamento identifique uma
 * adesão, e só uma.
 */
class EnforceUniqueStorageUuid extends BaseMigration
{
    public function up(): void
    {
        // Reatribui um uuid novo a cada duplicata, preservando o da adesão
        // mais antiga de cada grupo. O link dessas linhas já era ambíguo, e
        // toda a base existente é de testes internos.
        $this->execute(
            'UPDATE adhesion_initial_data AS a
                SET storage_uuid = gen_random_uuid()::text
              WHERE EXISTS (
                    SELECT 1
                      FROM adhesion_initial_data AS b
                     WHERE b.storage_uuid = a.storage_uuid
                       AND b.id < a.id
                    )'
        );

        $this->table('adhesion_initial_data')
            ->addIndex(['storage_uuid'], ['unique' => true, 'name' => 'adhesion_initial_data_storage_uuid_unique'])
            ->update();
    }

    public function down(): void
    {
        // Os uuids reatribuídos não voltam: o estado anterior era justamente
        // o que não se consegue reconstruir, já que não havia como saber qual
        // adesão cada link duplicado deveria abrir.
        $this->table('adhesion_initial_data')
            ->removeIndexByName('adhesion_initial_data_storage_uuid_unique')
            ->update();
    }
}
