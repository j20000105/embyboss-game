<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emby extends Model
{
    protected $primaryKey = 'tg';

    protected $table = 'emby';

    protected $guarded = [];

    const CREATED_AT = null;

    const UPDATED_AT = null;
}
