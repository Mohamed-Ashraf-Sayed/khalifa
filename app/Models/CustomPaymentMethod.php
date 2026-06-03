<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPaymentMethod extends Model
{
    protected $fillable = [
        'name', 'code', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
