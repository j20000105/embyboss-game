<?php

namespace App\Http\Controllers\Telegram\Commands;

use App\Models\Emby;
use App\Models\Game;
use App\Models\GamePlay;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NumberGame extends BaseCommand
{
    protected string $name = 'number_game';

    protected string $description = '猜数字游戏 | 群组';

    public function handle(): void
    {
        if (! $this->inGroup()) {
            $this->replyWithMessage([
                'text' => '仅限群组中使用',
            ]);

            return;
        }
        $text = $this->getUpdate()->getMessage()->text;
        $game = explode(' ', $text);
        $coinName = config('game.coin_name');
        if (count($game) < 2) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => <<<'NOTICE'
指令格式如下：
/number_game 1 3
至少投注一个数字，多个数字，请在中间添加英文空格
NOTICE,
            ]);

            return;
        }
        $numbers = [];
        foreach ($game as $key => $value) {
            if ($key == 0) {
                continue;
            }
            if (! is_numeric($value)) {
                $this->replyWithMessage([
                    'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                    'text' => '请输入数字',
                ]);

                return;
            }
            $numbers[] = $value;
        }
        $numbers = array_unique($numbers);

        $from = $this->getUpdate()->getMessage()->from;
        $account = Emby::where('tg', $from->id)->first();
        if (empty($account)) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '你号呢？',
            ]);

            return;
        }

        $game = Game::where('type', 'number')->where('status', 'ongoing')->first();
        if (empty($game)) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '当前没有游戏进行中',
            ]);

            return;
        }
        $details = $game->details;
        $gameCosts = $details['costs'];
        // 查询已投注数字
        $numberPlaced = [];
        $records = GamePlay::where('game_id', $game->id)->where('tg_id', $from->id)->get();
        foreach ($records as $record) {
            $numberPlaced = array_merge($numberPlaced, $record->details['numbers']);
        }

        if (! empty(array_intersect($numbers, $numberPlaced))) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => sprintf('已经投注数字 %s 了', implode(',', $numberPlaced)),
            ]);

            return;
        }
        $totalNumberCount = count($numbers) + count($numberPlaced);
        // 计算消耗金币数
        if ($totalNumberCount > count($gameCosts)) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '最多投注'.count($gameCosts).'个数字',
            ]);

            return;
        }
        $needCost = $gameCosts[$totalNumberCount - 1];
        if (! empty($numberPlaced)) {
            // 减去已投注的金币数
            $needCost -= $gameCosts[count($numberPlaced) - 1];
        }
        if ($account->iv < $needCost) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => $coinName.'不足，快呼叫老板！',
            ]);

            return;
        }

        $lock = Cache::lock('number_game:'.$from->id, 3);
        if (! $lock->get()) {
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '游戏人数较多，请稍后再试',
            ]);

            return;
        }

        // 开启事务
        DB::beginTransaction();
        try {
            $res = $account->where('iv', '>=', $needCost)->decrement('iv', $needCost);
            if (empty($res)) {
                DB::rollback();
                $lock->release();
                $this->replyWithMessage([
                    'text' => $coinName.'扣减失败',
                ]);

                return;
            }
            GamePlay::create([
                'tg_id' => $from->id,
                'tg_name' => trim($from->first_name.' '.$from->last_name),
                'game_id' => $game->id,
                'coins' => $needCost,
                'before_coins' => $account->iv,
                'after_coins' => $account->iv - $needCost,
                'details' => [
                    'numbers' => $numbers,
                ],
            ]);
            $game->increment('total_coins', $needCost);
            if (empty($numberPlaced)) {
                // 第一次参与，统计人数
                $game->increment('total_players');
            }
            DB::commit();
            $lock->release();
        } catch (\Throwable $e) {
            DB::rollback();
            $lock->release();
            Log::error('参与游戏失败:'.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->replyWithMessage([
                'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
                'text' => '参与失败',
            ]);

            return;
        }

        $type = '参与';
        if (! empty($numberPlaced)) {
            $type = '追加';
        }
        $this->replyWithMessage([
            'reply_to_message_id' => $this->getUpdate()->getMessage()->message_id,
            'text' => $type.'成功，本次消耗 '.$needCost.' '.$coinName,
        ]);
    }
}
