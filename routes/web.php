<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\HelloController::class, 'hello']);

Route::prefix('telegram')->withoutMiddleware('web')
    ->group(function () {
        Route::any(config('telegram.webhook_disguise').'/webhook', [\App\Http\Controllers\Telegram\WebhookController::class, 'update']);
    });
