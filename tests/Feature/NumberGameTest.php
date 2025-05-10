<?php

namespace Tests\Feature;

use App\Models\Emby;
use App\Models\Game;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_game_param(): void
    {
        $mock = $this->partialMock(TelegramService::class);
        $mock->expects('sendMessage')->times(3);

        $webhook = 'telegram/'.config('telegram.webhook_disguise').'/webhook';
        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/start_game number',
                'entities' => [
                    ['length' => 11],
                ],
            ],
        ]));
        $response->assertStatus(200);

        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/start_game number abc',
                'entities' => [
                    ['length' => 11],
                ],
            ],
        ]));
        $response->assertStatus(200);

        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/start_game number 0-9 abc',
                'entities' => [
                    ['length' => 11],
                ],
            ],
        ]));
        $response->assertStatus(200);

        $this->assertEquals(0, Game::count());
    }

    public function test_start_game_normal(): void
    {
        $mock = $this->partialMock(TelegramService::class);
        $mock->expects('sendMessage')->times(6);

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
        $response->assertStatus(200);

        // 重复创建
        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/start_game number 1-100 100 500 999',
                'entities' => [
                    ['length' => 11],
                ],
            ],
        ]));
        $response->assertStatus(200);

        // 重复的被过滤，只有一个
        $this->assertEquals(1, Game::count());
        $game = Game::first();
        $this->assertEquals([1, 100], $game->details['range']);
        $this->assertEquals([100, 500, 999], $game->details['costs']);

        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/number_game 1',
                'entities' => [
                    ['length' => 12],
                ],
            ],
        ]));
        $response->assertStatus(200);

        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/number_game 2',
                'entities' => [
                    ['length' => 12],
                ],
            ],
        ]));
        $response->assertStatus(200);

        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/number_game 3',
                'entities' => [
                    ['length' => 12],
                ],
            ],
        ]));
        $response->assertStatus(200);

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
        $response->assertStatus(200);

        $coins = Emby::where('tg', 111)->first();
        $this->assertEquals(899, $coins->iv);

        $other = Emby::where('tg', 222)->first();
        $this->assertEquals(999000123, $other->iv);
    }

    public function test_start_game_with_closing_time(): void
    {
        $mock = $this->partialMock(TelegramService::class);
        $mock->expects('sendMessage')->times(2);

        Emby::create([
            'tg' => 111,
            'iv' => 999,
        ]);
        $webhook = 'telegram/'.config('telegram.webhook_disguise').'/webhook';
        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/start_game number 1-100 100 500 999 --closing-time 2000-01-01 00:00',
                'entities' => [
                    ['length' => 11],
                ],
            ],
        ]));
        $response->assertStatus(200);

        $this->assertEquals(1, Game::count());
        $game = Game::first();
        $this->assertEquals('2000-01-01 00:00', $game->details['closing_time']);

        // 超过时间，投注失败，所以不扣除金币
        $response = $this->postJson($webhook, $this->commandRequest([
            'message' => [
                'text' => '/number_game 2',
                'entities' => [
                    ['length' => 12],
                ],
            ],
        ]));
        $response->assertStatus(200);

        $coins = Emby::where('tg', 111)->first();
        $this->assertEquals(999, $coins->iv);
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
