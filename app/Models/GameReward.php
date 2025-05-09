<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameReward extends Model
{
    protected $guarded = [];

    protected $casts = [
        'details' => 'array',
    ];
}
