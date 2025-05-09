<?php

use App\Http\Controllers\Telegram\Commands;

return [
    'webhook_disguise' => env('TELEGRAM_WEBHOOK_DISGUISE'),
    'bots' => [
        'userbot' => [
            'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR-BOT-TOKEN'),
            'webhook_url' => env('TELEGRAM_WEBHOOK_URL', 'YOUR-BOT-WEBHOOK-URL'),
            'commands' => [
                Commands\StartGame::class,
                Commands\FinishGame::class,
                Commands\NumberGame::class,
            ],
        ],
    ],
    'default' => 'userbot',
    'async_requests' => false,
    'http_client_handler' => null,
    'base_bot_url' => null,
    'resolve_command_dependencies' => true,
    'commands' => [
        Commands\Help::class,
    ],
    'command_groups' => [],
    'shared_commands' => [],
    'group_id' => env('TELEGRAM_GROUP_ID', 'YOUR-GROUP-ID'),
    'admin_ids' => env('TELEGRAM_ADMIN_IDS'),
];
