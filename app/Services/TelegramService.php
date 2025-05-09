<?php

namespace App\Services;

use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    public function sendMessage($message)
    {
        Telegram::sendMessage($message);
    }

    public function escape($text)
    {
        $escapeChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];

        return str_replace(
            $escapeChars,
            array_map(fn ($c) => "\\$c", $escapeChars),
            $text
        );
    }
}
