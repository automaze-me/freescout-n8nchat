<?php

return [
    'name' => 'N8nChat',

    // Option defaults. Stored via \Option with the "n8nchat." prefix.
    'options' => [
        'enabled'           => ['default' => false],
        'webhook_url'       => ['default' => ''],
        'shared_secret'     => ['default' => ''],
        'secret_header'     => ['default' => 'X-Freescout-Secret'],
        'streaming'         => ['default' => false],
        'title'             => ['default' => ''],
        'subtitle'          => ['default' => ''],
        'greeting'          => ['default' => ''],
        'input_placeholder' => ['default' => ''],
    ],
];
