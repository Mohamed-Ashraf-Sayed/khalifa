<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class LetterOfGuarantee extends Model
{
    use SoftDeletes;

    protected $table = 'letters_of_guarantee';

    public const TYPES = [
        'bid' => 'ابتدائي (دخول مناقصة)',
        'performance' => 'نهائي (حسن تنفيذ)',
        'advance' => 'دفعة مقدمة',
    ];

    public const STATUSES = [
        'active' => 'ساري',
        'released' => 'مُفرج عنه',
        'expired' => 'منتهي',
        'cancelled' => 'ملغي',
    ];

    protected $fillable = [
        'lg_number', 'type', 'beneficiary', 'bank_name', 'bank_account_id',
        'amount', 'issue_date', 'expiry_date', 'status', 'project_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** الخطاب ساري وقارب على الانتهاء خلال المدة المحددة (افتراضي 30 يوم). */
    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->status !== 'active' || ! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->betweenIncluded(Carbon::today(), Carbon::today()->addDays($days));
    }

    /** الخطاب انتهت صلاحيته. */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
