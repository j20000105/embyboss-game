<?php

namespace App\Http\Controllers\Telegram\Commands;

class Help extends BaseCommand
{
    protected string $name = 'help';

    protected string $description = '帮助列表';

    public function handle(): void
    {
        $commands = $this->telegram->getCommandBus()->getCommands();

        $text = '';
        foreach ($commands as $name => $handler) {
            $text .= sprintf('/%s - %s'.PHP_EOL, $name, $handler->getDescription());
        }

        $this->replyWithMessage(['text' => $text]);
    }
}
