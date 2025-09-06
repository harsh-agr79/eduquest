<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FPS extends Model
{
    protected $fillable = ['name', 'data'];

    protected $casts = [
        'data' => 'array',
    ];
}
