<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiBlogRun extends Model
{
    protected $guarded = [];

    protected $casts = ['options' => 'array'];
}
