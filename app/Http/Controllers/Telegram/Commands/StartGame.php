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
            $this->replyMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '仅限管理员使用',
            ]);

            return;
        }
        $text = $this->getUpdate()->getMessage()->text;
        $gameCommand = $this->parseCommand($text);
        $coinName = config('game.coin_name');
        $commandExample = <<<EXAMPLE
指令格式如下：
1、猜数字游戏
/start_game number 0-9 500 2000 --closing-time 21:00
0-9 为数字范围，可自定义
数字为投注指定个数所需花费{$coinName}，同时也限制最大投注个数，至少填写一个
closing-time 为封盘时间，不设置则一直可以投注，格式为 2025-01-01 08:00 或 08:00，如果只填写时间，当天未到该时间点则为当天，否则为第二天
EXAMPLE;
        if (empty($gameCommand['command']) || empty($gameCommand['params'])) {
            $this->replyMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => $commandExample,
            ]);

            return;
        }

        switch ($gameCommand['params'][0]) {
            case 'number':
                $this->numberGame($gameCommand);
                break;
            default:
                $this->replyMessage([
                    'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                    'text' => $commandExample,
                ]);
                break;
        }
    }

    public function numberGame($gameCommand)
    {
        $params = $gameCommand['params'];
        $options = $gameCommand['options'];

        $coinName = config('game.coin_name');
        if (count($params) < 3) {
            $this->replyMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => <<<EXAMPLE
指令格式如下：
/start_game number 0-9 500 2000 --closing-time 21:00
0-9 为数字范围，可自定义
数字为投注指定个数所需花费{$coinName}，同时也限制最大投注个数，至少填写一个
closing-time 为封盘时间，不设置则一直可以投注，格式为 2025-01-01 08:00 或 08:00，如果只填写时间，当天未到该时间点则为当天，否则为第二天
EXAMPLE,
            ]);

            return;
        }
        $range = $params[1];
        $range = explode('-', $range);
        if (count($range) != 2
            || intval($range[0]) != $range[0]
            || intval($range[1]) != $range[1]
            || $range[0] > $range[1]
        ) {
            $this->replyMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '范围设置错误，正确格式为：数字-数字',
            ]);

            return;
        }
        $range = array_map('intval', $range);

        $costs = [];
        $explains = [];
        foreach ($params as $key => $value) {
            if ($key < 2) {
                continue;
            }
            if (! is_numeric($value) || intval($value) != $value) {
                $this->replyMessage([
                    'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                    'text' => '请输入整数数字',
                ]);

                return;
            }
            $costs[] = intval($value);
            $explains[] = sprintf('投注 %s 个数字，总花费 %s %s', $key - 1, $value, $coinName);
        }

        if (! empty($options['closing-time'])) {
            // 检查时间格式
            $closingTime = $options['closing-time'];
            // 检查时间格式是否符合要求
            if (! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $closingTime)
                && ! preg_match('/^\d{2}:\d{2}$/', $closingTime)) {
                $this->replyMessage([
                    'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                    'text' => '时间格式错误，正确格式为: 2025-01-01 08:00 或 08:00',
                ]);

                return;
            }

            // 如果只输入时间，需要自动补充日期
            if (preg_match('/^\d{2}:\d{2}$/', $closingTime)) {
                $now = now();
                $today = $now->format('Y-m-d');
                $closingTime = $today.' '.$closingTime;

                // 如果设定时间已过，自动设置为明天
                if (strtotime($closingTime) < $now->timestamp) {
                    $closingTime = $now->addDay()->format('Y-m-d').' '.$options['closing-time'];
                }
            }

            // 验证时间是否有效
            if (! strtotime($closingTime)) {
                $this->replyMessage([
                    'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                    'text' => '无效的时间格式',
                ]);

                return;
            }
        }

        $currentGame = Game::where('type', 'number')->where('status', 'ongoing')->first();
        if ($currentGame) {
            $this->replyMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '当前有游戏正在进行，请等待...',
            ]);

            return;
        }

        $gameRecord = [
            'status' => 'ongoing',
            'type' => 'number',
            'details' => [
                'range' => $range,
                'costs' => $costs,
            ],
            'creator_tg_id' => $this->getUpdate()->getMessage()->from->id,
        ];
        if (! empty($options['closing-time'])) {
            $gameRecord['details']['closing_time'] = $closingTime;
        }
        Game::create($gameRecord);

        $explainText = implode("\n", $explains);
        $gameInfo = <<<TEXT
猜数字游戏开始
数字范围为 {$range[0]} 到 {$range[1]}
{$explainText}
TEXT;
        if (! empty($options['closing-time'])) {
            $gameInfo .= "\n封盘时间为 {$closingTime}";
        }
        $this->replyMessage([
            'chat_id' => config('telegram.group_id'),
            'text' => $gameInfo,
        ]);
    }
}
