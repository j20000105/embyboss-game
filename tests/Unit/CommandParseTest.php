<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CommandParseTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_command_parse(): void
    {
        $command = new \App\Http\Controllers\Telegram\Commands\Help;
        $result = $command->parseCommand('/help');
        $this->assertEquals('help', $result['command']);
        $this->assertEquals([], $result['params']);

        $result = $command->parseCommand('/help 1 2 3');
        $this->assertEquals('help', $result['command']);
        $this->assertEquals(['1', '2', '3'], $result['params']);

        $result = $command->parseCommand('/help 1 2 3 --a 2025-01-01 00:00 --b 2');
        $this->assertEquals('help', $result['command']);
        $this->assertEquals(['1', '2', '3'], $result['params']);
        $this->assertEquals(['a' => '2025-01-01 00:00', 'b' => '2'], $result['options']);
    }
}
