<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Asset extends Model
{
    protected $fillable = [
        'asset_code', 'asset_name', 'category', 'purchase_date',
        'purchase_value', 'salvage_value', 'depreciation_rate', 'depreciation_method', 'useful_life_years',
        'status', 'disposal_date', 'disposal_value', 'location', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_value' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'depreciation_rate' => 'decimal:2',
            'disposal_value' => 'decimal:2',
            'purchase_date' => 'date',
            'disposal_date' => 'date',
        ];
    }

    public const STATUSES = [
        'active' => 'نشط',
        'sold' => 'مُباع',
        'disposed' => 'مُستبعد',
        'fully_depreciated' => 'مُستهلك بالكامل',
    ];

    public const METHODS = [
        'straight_line' => 'القسط الثابت',
        'declining' => 'القسط المتناقص',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** أساس الإهلاك = التكلفة − قيمة الخردة. */
    public function depreciableBase(): string
    {
        return bcsub((string) $this->purchase_value, (string) ($this->salvage_value ?? 0), 2);
    }

    /**
     * جدول الإهلاك السنوي (مصدر موثوق): لكل سنة — رصيد أول المدة، الإهلاك، المجمّع، رصيد آخر المدة.
     * لا يُهلَك الأصل تحت قيمة الخردة.
     *
     * @return array<int, array{year:int, opening:string, depreciation:string, accumulated:string, closing:string}>
     */
    public function depreciationSchedule(): array
    {
        $life = max(1, (int) $this->useful_life_years);
        $salvage = (string) ($this->salvage_value ?? 0);
        $base = $this->depreciableBase();
        $rows = [];
        $acc = '0.00';
        $book = (string) $this->purchase_value;

        for ($y = 1; $y <= $life; $y++) {
            if ($this->depreciation_method === 'declining') {
                $dep = bcdiv(bcmul($book, (string) $this->depreciation_rate, 4), '100', 2);
            } else { // القسط الثابت
                $dep = bcdiv($base, (string) $life, 2);
            }
            // لا تتجاوز ما يبقّي الرصيد عند قيمة الخردة
            $maxDep = bcsub($book, $salvage, 2);
            if (bccomp($dep, $maxDep, 2) > 0) {
                $dep = $maxDep;
            }
            if (bccomp($dep, '0', 2) < 0) {
                $dep = '0.00';
            }
            $acc = bcadd($acc, $dep, 2);
            $closing = bcsub($book, $dep, 2);
            $rows[] = ['year' => $y, 'opening' => $book, 'depreciation' => $dep, 'accumulated' => $acc, 'closing' => $closing];
            $book = $closing;
        }

        return $rows;
    }

    /** عدد الأشهر المنقضية في الخدمة (محدودة بالعمر الإنتاجي، وعند الاستبعاد تتوقف عند تاريخه). */
    public function monthsInService(): int
    {
        if (! $this->purchase_date) {
            return 0;
        }
        $end = $this->disposal_date ?: Carbon::today();
        $months = $this->purchase_date->diffInMonths($end);
        $cap = max(1, (int) $this->useful_life_years) * 12;

        return (int) min(max($months, 0), $cap);
    }

    /** مجمّع الإهلاك حتى الآن (بدقّة شهرية، لا يتجاوز أساس الإهلاك). */
    public function accumulatedDepreciation(): string
    {
        $schedule = $this->depreciationSchedule();
        if (empty($schedule)) {
            return '0.00';
        }
        $months = $this->monthsInService();
        $fullYears = intdiv($months, 12);
        $fracMonths = $months % 12;

        $acc = '0.00';
        for ($i = 0; $i < $fullYears && $i < count($schedule); $i++) {
            $acc = bcadd($acc, $schedule[$i]['depreciation'], 2);
        }
        if ($fracMonths > 0 && $fullYears < count($schedule)) {
            $partial = bcdiv(bcmul($schedule[$fullYears]['depreciation'], (string) $fracMonths, 4), '12', 2);
            $acc = bcadd($acc, $partial, 2);
        }

        $base = $this->depreciableBase();
        if (bccomp($acc, $base, 2) > 0) {
            $acc = $base;
        }

        return $acc;
    }

    /** القيمة الدفترية الحالية = التكلفة − مجمّع الإهلاك (لا تقل عن قيمة الخردة). */
    public function bookValue(): string
    {
        $value = bcsub((string) $this->purchase_value, $this->accumulatedDepreciation(), 2);
        $salvage = (string) ($this->salvage_value ?? 0);

        return bccomp($value, $salvage, 2) < 0 ? $salvage : $value;
    }

    /** قسط الإهلاك السنوي (تقريبي للعرض). */
    public function annualDepreciation(): string
    {
        if ($this->depreciation_method === 'declining') {
            return bcdiv(bcmul($this->bookValue(), (string) $this->depreciation_rate, 4), '100', 2);
        }

        return bcdiv($this->depreciableBase(), (string) max(1, (int) $this->useful_life_years), 2);
    }

    public function monthlyDepreciation(): string
    {
        return bcdiv($this->annualDepreciation(), '12', 2);
    }

    public function isFullyDepreciated(): bool
    {
        return bccomp($this->bookValue(), (string) ($this->salvage_value ?? 0), 2) <= 0;
    }

    /** ربح/خسارة الاستبعاد = قيمة البيع − القيمة الدفترية. */
    public function disposalGainLoss(): ?string
    {
        if ($this->disposal_value === null) {
            return null;
        }

        return bcsub((string) $this->disposal_value, $this->bookValue(), 2);
    }
}
