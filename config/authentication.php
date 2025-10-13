<?php
return [
    'Authentication' => [
        'default' => [
            'identifier' => [
                'className' => 'Authentication.Password',
                'fields' => [
                    'username' => 'email',
                    'password' => 'password',
                ],
            ],
            'authenticator' => [
                'Authentication.Session',
                'Authentication.Form' => [
                    'fields' => [
                        'username' => 'email',
                        'password' => 'password',
                    ],
                    'loginUrl' => '/admin/users/login',
                ],
            ],
        ],
    ],
];
