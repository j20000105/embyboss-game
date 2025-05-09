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
指令格式如下：
1、猜数字游戏
/start_game number 0-9 500 2000 10000
0-9 为数字范围，可自定义
数字为投注指定个数所需花费{$coinName}，同时也限制最大投注个数，至少填写一个
EXAMPLE;
        if (count($game) < 4) {
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
        $range = $game[2];
        $range = explode('-', $range);
        if (count($range) != 2
            || intval($range[0]) != $range[0]
            || intval($range[1]) != $range[1]
            || $range[0] > $range[1]
        ) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '范围设置错误，正确格式为：数字-数字',
            ]);

            return;
        }
        $range = array_map('intval', $range);

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
            if ($key < 3) {
                continue;
            }
            if (! is_numeric($value) || intval($value) != $value) {
                $this->replyWithMessage([
                    'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                    'text' => '请输入整数数字',
                ]);

                return;
            }
            $costs[] = intval($value);
            $explains[] = sprintf('投注 %s 个数字，总花费 %s %s', $key - 2, $value, $coinName);
        }
        Game::create([
            'status' => 'ongoing',
            'type' => 'number',
            'details' => [
                'range' => $range,
                'costs' => $costs,
            ],
            'creator_tg_id' => $this->getUpdate()->getMessage()->from->id,
        ]);

        $this->replyWithMessage([
            'chat_id' => config('telegram.group_id'),
            'text' => sprintf("猜数字游戏开始\n数字范围为 %d 到 %d\n%s", $range[0], $range[1], implode("\n", $explains)),
        ]);
    }
}
