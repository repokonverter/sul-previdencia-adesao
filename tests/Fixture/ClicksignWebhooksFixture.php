<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Sem registros de propósito: nenhum teste depende de um webhook já
 * cadastrado, só da tabela existir -- e sem linha semeada não há id fixo
 * para colidir com o que um INSERT de teste gerar.
 */
class ClicksignWebhooksFixture extends TestFixture
{
}
