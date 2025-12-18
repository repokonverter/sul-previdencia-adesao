<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateCbosTable extends AbstractMigration
{
  /**
   * Change Method.
   *
   * More information on this method is available here:
   * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
   * @return void
   */
  public function change(): void
  {
    $table = $this->table('cbos');
    $table->addColumn('code', 'string', [
      'default' => null,
      'limit' => 10,
      'null' => false,
    ]);
    $table->addColumn('description', 'string', [
      'default' => null,
      'limit' => 255,
      'null' => false,
    ]);
    $table->addColumn('created', 'datetime', [
      'default' => null,
      'null' => false,
    ]);
    $table->addColumn('modified', 'datetime', [
      'default' => null,
      'null' => false,
    ]);
    $table->addIndex([
      'code',
    ], [
      'name' => 'BY_CODE',
      'unique' => false,
    ]);
    $table->create();
  }
}
