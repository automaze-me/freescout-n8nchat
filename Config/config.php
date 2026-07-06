<?php

return [
    'name' => 'N8nChat',

    // Option defaults. Stored via \Option with the "n8nchat." prefix.
    'options' => [
        'enabled'           => ['default' => false],
        'webhook_url'       => ['default' => ''],
        'auth_username'     => ['default' => ''],
        'auth_password'     => ['default' => ''],
        'streaming'         => ['default' => false],
        'title'             => ['default' => ''],
        'subtitle'          => ['default' => ''],
        'greeting'          => ['default' => ''],
        'input_placeholder' => ['default' => ''],
    ],
];
