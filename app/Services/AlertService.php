<?php

namespace App\Services;

use App\Models\ContractorExtract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\PartnerProfitSchedule;
use App\Models\Revenue;
use Illuminate\Support\Carbon;

/**
 * يجمّع التنبيهات التشغيلية الحرجة لعرضها في مركز إشعارات الهيدر ولوحة التحكم:
 * فواتير متأخرة، تحصيلات/مصروفات مستحقة، مخزون منخفض، أقساط أرباح شركاء مستحقة.
 */
class AlertService
{
    /** @return array<int, array{icon:string,color:string,label:string,count:int,url:string}> */
    public function items(): array
    {
        $today = Carbon::today()->toDateString();

        $overdueInvoices = Invoice::whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereNotNull('due_date')->whereDate('due_date', '<', $today)->count();

        $dueRevenues = Revenue::where('payment_status', '!=', 'collected')
            ->whereNotNull('due_date')->whereDate('due_date', '<=', $today)->count();

        $overdueExpenses = Expense::where('is_credit', true)->where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')->whereDate('due_date', '<', $today)->count();

        $lowStock = Material::lowStock()->count();

        $duePartnerProfits = PartnerProfitSchedule::where('is_paid', false)
            ->whereDate('due_date', '<=', $today)->count();

        $unpaidExtracts = ContractorExtract::whereIn('status', ['approved', 'partial'])->count();

        $all = [
            ['icon' => 'fa-file-invoice', 'color' => 'danger', 'label' => 'فواتير متأخرة', 'count' => $overdueInvoices, 'url' => route('invoices.index', ['status' => 'overdue'])],
            ['icon' => 'fa-hand-holding-dollar', 'color' => 'warning', 'label' => 'إيرادات مستحقة التحصيل', 'count' => $dueRevenues, 'url' => route('revenues.index', ['payment_status' => 'pending'])],
            ['icon' => 'fa-money-bill-wave', 'color' => 'warning', 'label' => 'مصروفات آجلة متأخرة', 'count' => $overdueExpenses, 'url' => route('expenses.index')],
            ['icon' => 'fa-boxes-stacked', 'color' => 'danger', 'label' => 'أصناف تحت حد المخزون', 'count' => $lowStock, 'url' => route('materials.index', ['low_stock' => 1])],
            ['icon' => 'fa-coins', 'color' => 'info', 'label' => 'أقساط أرباح شركاء مستحقة', 'count' => $duePartnerProfits, 'url' => route('partner_deposits.index')],
            ['icon' => 'fa-file-contract', 'color' => 'secondary', 'label' => 'مستخلصات غير مسدّدة بالكامل', 'count' => $unpaidExtracts, 'url' => route('contractor_extracts.index')],
        ];

        return array_values(array_filter($all, fn ($a) => $a['count'] > 0));
    }

    public function total(): int
    {
        return array_sum(array_column($this->items(), 'count'));
    }
}
