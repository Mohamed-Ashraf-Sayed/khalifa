<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'asset' => 'أصول',
        'liability' => 'التزامات',
        'equity' => 'حقوق ملكية',
        'revenue' => 'إيرادات',
        'expense' => 'مصروفات',
    ];

    public const NORMAL = [
        'debit' => 'مدين',
        'credit' => 'دائن',
    ];

    /** أكواد الحسابات المعروفة لطبقة الترحيل المحاسبي. */
    public const CODES = [
        'cash' => '1101',
        'bank' => '1102',
        'ar' => '1103',
        'inventory' => '1105',
        'wip' => '1106',
        'employee_advances' => '1107',
        'vat_input' => '1108',
        'fixed_assets' => '1201',
        'accum_depreciation' => '1202',
        'ap' => '2101',
        'subcontractors' => '2102',
        'vat_output' => '2104',
        'taxes_payable' => '2105',
        'salaries_payable' => '2106',
        'retention' => '2107',
        'capital' => '3101',
        'partners_current' => '3102',
        'contract_revenue' => '4101',
        'other_revenue' => '4102',
        'contractor_cost' => '5101',
        'material_cost' => '5102',
        'labor_cost' => '5103',
        'equipment_cost' => '5104',
        'salaries_expense' => '5201',
        'depreciation_expense' => '5205',
        'general_expense' => '5204',
    ];

    protected $fillable = [
        'code', 'name', 'type', 'parent_id', 'is_group',
        'normal_balance', 'opening_balance', 'is_active', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'is_active' => 'boolean',
            'opening_balance' => 'decimal:2',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /** مجموع المدين المُرحَّل (من القيود ذات الحالة posted فقط، حتى تاريخ asOf إن وُجد). */
    public function postedDebit(?string $asOf = null): string
    {
        return (string) DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.account_id', $this->id)
            ->where('journal_entries.status', 'posted')
            ->when($asOf, fn ($q) => $q->whereDate('journal_entries.entry_date', '<=', $asOf))
            ->sum('journal_entry_lines.debit');
    }

    /** مجموع الدائن المُرحَّل (من القيود ذات الحالة posted فقط، حتى تاريخ asOf إن وُجد). */
    public function postedCredit(?string $asOf = null): string
    {
        return (string) DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.account_id', $this->id)
            ->where('journal_entries.status', 'posted')
            ->when($asOf, fn ($q) => $q->whereDate('journal_entries.entry_date', '<=', $asOf))
            ->sum('journal_entry_lines.credit');
    }

    /**
     * الرصيد المُشتقّ = الرصيد الافتتاحي + صافي الحركة باتجاه طبيعة الحساب.
     * مدين: opening + (debit − credit) | دائن: opening + (credit − debit).
     */
    public function balance(?string $asOf = null): string
    {
        $debit = $this->postedDebit($asOf);
        $credit = $this->postedCredit($asOf);
        $opening = (string) $this->opening_balance;

        return $this->normal_balance === 'debit'
            ? bcadd($opening, bcsub($debit, $credit, 2), 2)
            : bcadd($opening, bcsub($credit, $debit, 2), 2);
    }

    /** البحث عن حساب بمفتاح معروف من CODES أو بكوده المباشر. */
    public static function resolve(string $key): ?Account
    {
        return Account::where('code', self::CODES[$key] ?? $key)->first();
    }
}
