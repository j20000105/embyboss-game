<?php

namespace App\Http\Controllers;

use App\Models\Emby;
use App\Models\Game;

class HelloController extends Controller
{
    public function hello()
    {
        Emby::first();
        Game::first();

        return 'ok';
    }
}
