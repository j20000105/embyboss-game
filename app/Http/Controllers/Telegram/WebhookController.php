<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    protected $telegram;

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    public function update(Request $request)
    {
        Log::info('telegram webhook get message', $request->all());

        if (config('app.env') === 'testing') {
            // 单测里拿不到Request，需要自行构造
            $tgRequest = new GuzzleRequest('POST', 'http://httpbin.org/post', [], json_encode($request->all()));
            Telegram::commandsHandler(true, $tgRequest);
        } else {
            Telegram::commandsHandler(true);
        }

        return 'ok';
    }
}
