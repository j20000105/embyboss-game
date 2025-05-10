<?php

namespace App\Http\Controllers\Telegram\Commands;

use App\Services\TelegramService;
use Telegram\Bot\Commands\Command;

abstract class BaseCommand extends Command
{
    public function isAdmin()
    {
        $from = $this->getUpdate()->getMessage()->from;
        $admins = explode(',', config('telegram.admin_ids'));

        return in_array($from->id, $admins);
    }

    public function inGroup()
    {
        $type = $this->getUpdate()->getMessage()->chat->type;

        return in_array($type, ['group', 'supergroup']);
    }

    /**
     * 解析指令, 第一个参数为 command 后续的参数为 params, -- 后面的参数为 options
     */
    public function parseCommand($text)
    {
        $result = [
            'command' => '',
            'params' => [],
            'options' => [],
        ];

        // 移除前导和尾随空格
        $text = trim($text);

        // 如果文本为空，直接返回空结果
        if (empty($text)) {
            return $result;
        }

        // 将文本按空格分割成数组
        $parts = preg_split('/\s+/', $text);

        // 第一个元素是命令
        $result['command'] = trim(array_shift($parts), '/');

        // 处理剩余部分
        $i = 0;
        while ($i < count($parts)) {
            $part = $parts[$i];

            // 检查是否是选项标识（以--开头）
            if (strpos($part, '--') === 0) {
                $optionKey = substr($part, 2); // 移除--前缀

                // 检查下一个部分是否存在且不是另一个选项
                if (isset($parts[$i + 1]) && strpos($parts[$i + 1], '--') !== 0) {
                    // 收集选项值，可能包含空格
                    $optionValue = '';
                    $j = $i + 1;

                    while ($j < count($parts) && strpos($parts[$j], '--') !== 0) {
                        $optionValue .= ($optionValue ? ' ' : '').$parts[$j];
                        $j++;
                    }

                    $result['options'][$optionKey] = $optionValue;
                    $i = $j; // 跳过已处理的部分
                } else {
                    // 没有值的选项
                    $result['options'][$optionKey] = true;
                    $i++;
                }
            } else {
                // 普通参数
                $result['params'][] = $part;
                $i++;
            }
        }

        return $result;
    }

    public function replyMessage($message)
    {
        $chatId = $this->update->getChat()->id ?? null;
        if (empty($message['chat_id']) && ! empty($chatId)) {
            $message['chat_id'] = $chatId;
        }

        app(TelegramService::class)->sendMessage($message);
    }
}
