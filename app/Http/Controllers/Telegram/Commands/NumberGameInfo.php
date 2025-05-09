<?php

namespace App\Http\Controllers\Telegram\Commands;

use App\Models\Game;
use App\Models\GamePlay;

class NumberGameInfo extends BaseCommand
{
    protected string $name = 'number_game_info';

    protected string $description = '猜数字游戏投注信息 | 管理员';

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
        $coinName = config('game.coin_name');

        $game = Game::where('type', 'number')->where('status', 'ongoing')->first();
        if (empty($game)) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '当前没有游戏进行中',
            ]);

            return;
        }

        $numberCount = [];
        $numberDetails = '';
        $plays = GamePlay::where('game_id', $game->id)->get();
        if (! $plays->isEmpty()) {
            foreach ($plays as $play) {
                $numbers = $play->details['numbers'];
                foreach ($numbers as $n) {
                    if (empty($numberCount[$n])) {
                        $numberCount[$n] = 1;
                    } else {
                        $numberCount[$n]++;
                    }
                }
            }
        } else {
            $numberDetails = '无人投注';
        }
        ksort($numberCount);
        foreach ($numberCount as $n => $total) {
            $numberDetails .= sprintf("数字 %d 人数：%d\n", $n, $total);
        }

        $info = <<<INFO
总投注{$coinName}：{$game->total_coins}
总投注人数：{$game->total_players}
投注详情：
{$numberDetails}
INFO;
        $this->replyWithMessage([
            'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
            'text' => $info,
        ]);
    }
}
