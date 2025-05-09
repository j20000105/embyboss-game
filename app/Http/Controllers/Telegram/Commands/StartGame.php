<?php

namespace App\Http\Controllers\Telegram\Commands;

use App\Models\Game;

class StartGame extends BaseCommand
{
    protected string $name = 'start_game';

    protected string $description = '创建游戏 | 管理员';

    public function handle(): void
    {
        if (! $this->isAdmin()) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '仅限管理员使用',
            ]);

            return;
        }
        $text = $this->getUpdate()->getMessage()->text;
        $game = explode(' ', $text);
        $coinName = config('game.coin_name');
        $commandExample = <<<EXAMPLE
指令错误，请按照以下格式创建游戏：
猜数字游戏 /start_game number 500 2000 10000, 数字为投注指定个数所需消耗{$coinName}，同时也限制最大投注个数，至少填写一个
EXAMPLE;
        if (count($game) < 2) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => $commandExample,
            ]);

            return;
        }
        switch ($game[1]) {
            case 'number':
                $this->numberGame($game);
                break;
            default:
                $this->replyWithMessage([
                    'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                    'text' => $commandExample,
                ]);
                break;
        }
    }

    public function numberGame($game)
    {
        if (count($game) < 3) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '请至少填写一个数字',
            ]);

            return;
        }
        $currentGame = Game::where('type', 'number')->where('status', 'ongoing')->first();
        if ($currentGame) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '当前有游戏正在进行，请等待...',
            ]);

            return;
        }
        $costs = [];
        $explains = [];
        $coinName = config('game.coin_name');
        foreach ($game as $key => $value) {
            if ($key < 2) {
                continue;
            }
            if (! is_numeric($value)) {
                $this->replyWithMessage([
                    'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                    'text' => '请输入数字',
                ]);

                return;
            }
            $costs[] = $value;
            $explains[] = sprintf('投注 %s 个数字，消耗 %s %s', $key - 1, $value, $coinName);
        }
        Game::create([
            'status' => 'ongoing',
            'type' => 'number',
            'details' => [
                'costs' => $costs,
            ],
            'creator_tg_id' => $this->getUpdate()->getMessage()->from->id,
        ]);

        $this->replyWithMessage([
            'chat_id' => config('telegram.group_id'),
            'text' => sprintf("猜数字游戏开始，详情：\n%s", implode("\n", $explains)),
        ]);
    }
}
