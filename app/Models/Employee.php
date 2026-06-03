<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_code', 'name', 'national_id', 'job_title', 'department',
        'salary', 'phone', 'email', 'hire_date', 'is_active', 'bank_account_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
            'hire_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(EmployeeTransaction::class);
    }

    /** رصيد السلف المستحقّ على الموظف = السلف − سدادها. */
    public function advanceBalance(): string
    {
        $adv = (string) $this->transactions()->where('type', 'advance')->sum('amount');
        $ret = (string) $this->transactions()->where('type', 'advance_return')->sum('amount');

        return bcsub($adv, $ret, 2);
    }

    /** رصيد العهدة في يد الموظف = العهدة − ردّها − ما صُرف منها. */
    public function custodyBalance(): string
    {
        $cus = (string) $this->transactions()->where('type', 'custody')->sum('amount');
        $ret = (string) $this->transactions()->where('type', 'custody_return')->sum('amount');
        $exp = (string) $this->transactions()->where('type', 'custody_expense')->sum('amount');

        return bcsub(bcsub($cus, $ret, 2), $exp, 2);
    }
}
