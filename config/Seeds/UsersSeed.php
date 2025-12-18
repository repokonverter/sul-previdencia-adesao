<?php

declare(strict_types=1);

use Migrations\AbstractSeed;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class UsersSeed extends AbstractSeed
{
    public function run(): void
    {
        $hasher = new DefaultPasswordHasher();

        $data = [
            [
                'name' => 'admin',
                'email' => 'admin@sistema.com',
                'password' => $hasher->hash('123456'),
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->table('users')->truncate();
        $this->table('users')->insert($data)->save();
    }
}
