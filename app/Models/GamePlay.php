<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePlay extends Model
{
    protected $guarded = [];

    protected $casts = [
        'details' => 'array',
    ];
}
