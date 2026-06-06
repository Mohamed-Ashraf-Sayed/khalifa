<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'draft' => 'مسودة',
        'posted' => 'مرحّل',
    ];

    protected $fillable = [
        'entry_number', 'entry_date', 'description', 'reference_type', 'reference_id',
        'source', 'status', 'total_debit', 'total_credit', 'created_by', 'posted_by', 'posted_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /** إعادة احتساب الإجماليات من البنود (مصدر موثوق) عبر bcmath. */
    public function recomputeTotals(): void
    {
        $debit = '0';
        $credit = '0';

        foreach ($this->lines as $line) {
            $debit = bcadd($debit, (string) $line->debit, 2);
            $credit = bcadd($credit, (string) $line->credit, 2);
        }

        $this->total_debit = $debit;
        $this->total_credit = $credit;
        $this->save();
    }

    /** القيد متوازن: المدين = الدائن وأكبر من صفر. */
    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debit, (string) $this->total_credit, 2) === 0
            && bccomp((string) $this->total_debit, '0', 2) > 0;
    }
}
