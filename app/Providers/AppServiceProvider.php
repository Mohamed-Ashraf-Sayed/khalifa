<?php

namespace App\Providers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** الكيانات التي يُسجَّل عليها النشاط تلقائياً. */
    private const AUDITED = [
        \App\Models\Project::class, \App\Models\Client::class, \App\Models\Contractor::class,
        \App\Models\Supplier::class, \App\Models\Employee::class, \App\Models\Partner::class,
        \App\Models\Expense::class, \App\Models\Revenue::class, \App\Models\BankAccount::class,
        \App\Models\Invoice::class, \App\Models\PurchaseOrder::class, \App\Models\ContractorExtract::class,
        \App\Models\User::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // الـadmin يتجاوز كل فحوصات الصلاحيات (super-role)
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // تسجيل النشاط مركزياً (إضافة/تعديل/حذف) — بدون تعديل كل موديل
        foreach (self::AUDITED as $class) {
            $class::created(fn (Model $m) => ActivityLog::record('created', $m));
            $class::updated(fn (Model $m) => ActivityLog::record('updated', $m));
            $class::deleted(fn (Model $m) => ActivityLog::record('deleted', $m));
        }
    }
}
