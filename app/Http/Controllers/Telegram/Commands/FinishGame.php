<?php

namespace App\Http\Controllers\Telegram\Commands;

use App\Models\Emby;
use App\Models\Game;
use App\Models\GamePlay;
use App\Models\GameReward;
use Illuminate\Support\Facades\Cache;

class FinishGame extends BaseCommand
{
    protected string $name = 'finish_game';

    protected string $description = '结束游戏 | 管理员';

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
        $commandExample = <<<'EXAMPLE'
指令错误格式如下：
1、猜数字游戏
/finish_game number 5
数字为开奖结果
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
        if (count($game) !== 3) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '请输入中奖数字',
            ]);

            return;
        }
        $coinName = config('game.coin_name');
        $correctNumber = $game[2];
        $currentGame = Game::where('type', 'number')->where('status', 'ongoing')->first();
        if (! $currentGame) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '当前没有游戏正在进行',
            ]);

            return;
        }
        $range = $currentGame->details['range'];
        if (! in_array($correctNumber, range($range[0], $range[1]))) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => sprintf('中奖数字应该为 %d 至 %d', $range[0], $range[1]),
            ]);

            return;
        }
        $currentGame->win_details = [
            'correct_number' => intval($correctNumber),
        ];

        $plays = GamePlay::where('game_id', $currentGame->id)->get();
        if ($plays->isEmpty()) {
            $currentGame->status = 'finished';
            $currentGame->save();

            $this->replyWithMessage([
                'chat_id' => config('telegram.group_id'),
                'text' => '猜数字游戏结束，没有人参与',
            ]);

            return;
        }

        $correctPerson = [];
        foreach ($plays as $play) {
            $numbers = $play->details['numbers'];
            if (in_array($correctNumber, $numbers)) {
                $correctPerson[] = [
                    'tg_id' => $play->tg_id,
                    'tg_name' => $play->tg_name,
                ];
            }
        }
        $totalCoins = $currentGame->total_coins;

        if (empty($correctPerson)) {
            $currentGame->status = 'finished';
            $currentGame->save();
            $notice = <<<NOTICE
猜数字游戏结束
中奖数字：{$correctNumber}
总投注{$coinName}：{$totalCoins}
总投注人数：{$currentGame->total_players}
没有人中奖，全归老板！
NOTICE;
            $this->replyWithMessage([
                'chat_id' => config('telegram.group_id'),
                'text' => $notice,
            ]);

            return;
        }

        $lock = Cache::lock('finish_game:'.$currentGame->id, 10);
        if (! $lock->get()) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '开奖中，请勿频繁操作',
            ]);

            return;
        }

        $totalCorrectPerson = count($correctPerson);

        $feeRate = config('game.fee_rate');
        $eachCoins = floor($totalCoins / $totalCorrectPerson * (100 - $feeRate) / 100);
        foreach ($correctPerson as $personInfo) {
            $person = Emby::where('tg', $personInfo['tg_id'])->first();
            if (empty($person)) {
                $this->replyWithMessage([
                    'chat_id' => config('telegram.group_id'),
                    'text' => sprintf('用户 %s 账号已不存在', $personInfo['tg_id']),
                ]);

                continue;
            }

            GameReward::create([
                'game_id' => $currentGame->id,
                'tg_id' => $personInfo['tg_id'],
                'coins' => $eachCoins,
                'before_coins' => $person->iv,
                'after_coins' => $person->iv + $eachCoins,
            ]);

            $person
                ->where('tg', $person['tg'])
                ->increment('iv', $eachCoins);
        }
        $currentGame->status = 'finished';
        $currentGame->winner_count = $totalCorrectPerson;
        $currentGame->save();

        $personDetail = '';
        foreach ($correctPerson as $personInfo) {
            $personDetail .= sprintf("[%s](tg://user?id=%s)\n", telegramEscape($personInfo['tg_name']), $personInfo['tg_id']);
        }

        $notice = <<<NOTICE
猜数字游戏结束
中奖数字：{$correctNumber}
中奖人数：{$totalCorrectPerson}
总投注{$coinName}：{$totalCoins}
总投注人数：{$currentGame->total_players}
每人获得 {$eachCoins}
中奖名单：
{$personDetail}
NOTICE;

        $this->replyWithMessage([
            'chat_id' => config('telegram.group_id'),
            'parse_mode' => 'MarkdownV2',
            'text' => $notice,
        ]);
    }
}
