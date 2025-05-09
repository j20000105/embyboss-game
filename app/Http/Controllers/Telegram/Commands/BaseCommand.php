<?php

namespace App\Http\Controllers\Telegram\Commands;

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
}
