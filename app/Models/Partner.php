<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partner extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'national_id', 'address',
        'join_date', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
        ];
    }

    public const STATUSES = [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'settled' => 'مُسوّى',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
