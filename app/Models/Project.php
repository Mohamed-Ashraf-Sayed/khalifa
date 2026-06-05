<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'client_id', 'project_type', 'location', 'description',
        'contract_value', 'start_date', 'end_date', 'actual_end_date',
        'status', 'manager_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'contract_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'actual_end_date' => 'date',
        ];
    }

    public const STATUSES = [
        'pending' => 'قيد الانتظار',
        'in_progress' => 'جاري التنفيذ',
        'completed' => 'مكتمل',
        'on_hold' => 'متوقف',
        'cancelled' => 'ملغي',
    ];

    public const TYPES = [
        'building' => 'مبنى',
        'road' => 'طريق',
        'bridge' => 'كوبري',
        'residential' => 'سكني',
        'commercial' => 'تجاري',
        'other' => 'أخرى',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function projectEmployees(): HasMany
    {
        return $this->hasMany(ProjectEmployee::class);
    }

    public function assignedEmployees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_employees')
            ->withPivot(['id', 'role', 'start_date', 'end_date', 'notes'])
            ->withTimestamps();
    }

    public function materialConsumptions(): HasMany
    {
        return $this->hasMany(ProjectMaterialConsumption::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function contractorExtracts(): HasMany
    {
        return $this->hasMany(ContractorExtract::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function supplierTransactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    public function projectCosts(): HasMany
    {
        return $this->hasMany(ProjectCost::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(ProjectContract::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sort')->orderBy('planned_start');
    }

    public function dailySiteReports(): HasMany
    {
        return $this->hasMany(DailySiteReport::class)->latest('report_date');
    }

    public function laborAttendances(): HasMany
    {
        return $this->hasMany(LaborAttendance::class);
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class)->latest('request_date');
    }

    public function snags(): HasMany
    {
        return $this->hasMany(Snag::class)->latest();
    }

    public function rfis(): HasMany
    {
        return $this->hasMany(Rfi::class)->latest();
    }

    public function submittals(): HasMany
    {
        return $this->hasMany(Submittal::class)->latest();
    }
}
