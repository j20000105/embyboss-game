<?php

namespace Tests\Feature;

use App\Models\Emby;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal(): void
    {
        Emby::create([
            'tg' => 111,
            'iv' => 999,
        ]);
        Emby::create([
            'tg' => 222,
            'iv' => 999000123,
        ]);

        $webhook = 'telegram/'.config('telegram.webhook_disguise').'/webhook';
        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/start_game number 1-100 100 500 999',
                'entities' => [
                    ['length' => 11],
                ],
            ],
        ]));
        $response->assertSeeText('ok');
        $this->assertEquals(1, Game::count());

        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/number_game 1',
                'entities' => [
                    ['length' => 12],
                ],
            ],
        ]));
        $response->assertSeeText('ok');

        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/number_game 2',
                'entities' => [
                    ['length' => 12],
                ],
            ],
        ]));
        $response->assertSeeText('ok');

        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/number_game 3',
                'entities' => [
                    ['length' => 12],
                ],
            ],
        ]));
        $response->assertSeeText('ok');

        $coins = Emby::where('tg', 111)->first();
        $this->assertEquals(0, $coins->iv);

        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/finish_game number 2',
                'entities' => [
                    ['length' => 12],
                ],
            ],
        ]));
        $response->assertSeeText('ok');

        $coins = Emby::where('tg', 111)->first();
        $this->assertEquals(899, $coins->iv);

        $other = Emby::where('tg', 222)->first();
        $this->assertEquals(999000123, $other->iv);
    }

    protected function commandRequest($command = [])
    {
        return array_replace_recursive([
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 111,
                    'is_bot' => false,
                    'first_name' => 'J',
                    'username' => 'j',
                    'language_code' => 'zh-hans',
                    'is_premium' => true,
                ],
                'chat' => [
                    'id' => -100999,
                    'title' => 'MessageTest',
                    'type' => 'supergroup',
                ],
                'date' => time(),
                'text' => '',
                'entities' => [
                    [
                        'offset' => 0,
                        'length' => 1,
                        'type' => 'bot_command',
                    ],
                ],
            ],
        ], $command);
    }
}
