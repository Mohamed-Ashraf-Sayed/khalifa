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

        $soon = Carbon::today()->addDays(30)->toDateString();
        $expiringGuarantees = \App\Models\LetterOfGuarantee::where('status', 'active')
            ->whereBetween('expiry_date', [$today, $soon])->count();
        $expiringInsurance = \App\Models\InsurancePolicy::where('status', 'active')
            ->whereBetween('expiry_date', [$today, $soon])->count();
        $dueEquipment = \App\Models\EquipmentLog::whereNotNull('next_service_date')
            ->whereBetween('next_service_date', [$today, Carbon::today()->addDays(14)->toDateString()])
            ->distinct('asset_id')->count('asset_id');
        $pendingRequisitions = \App\Models\MaterialRequisition::where('status', 'pending')->count();

        $all = [
            ['icon' => 'fa-shield-halved', 'color' => 'warning', 'label' => 'خطابات ضمان قاربت على الانتهاء', 'count' => $expiringGuarantees, 'url' => route('guarantees.index')],
            ['icon' => 'fa-file-shield', 'color' => 'warning', 'label' => 'وثائق تأمين قاربت على الانتهاء', 'count' => $expiringInsurance, 'url' => route('insurance.index')],
            ['icon' => 'fa-screwdriver-wrench', 'color' => 'info', 'label' => 'معدات مستحقة الصيانة', 'count' => $dueEquipment, 'url' => route('equipment_logs.index')],
            ['icon' => 'fa-clipboard-check', 'color' => 'secondary', 'label' => 'أذون صرف بانتظار الاعتماد', 'count' => $pendingRequisitions, 'url' => route('material_requisitions.index', ['status' => 'pending'])],
            ['icon' => 'fa-file-invoice', 'color' => 'danger', 'label' => 'فواتير متأخرة', 'count' => $overdueInvoices, 'url' => route('invoices.index', ['status' => 'overdue'])],
            ['icon' => 'fa-hand-holding-dollar', 'color' => 'warning', 'label' => 'إيرادات مستحقة التحصيل', 'count' => $dueRevenues, 'url' => route('revenues.index', ['payment_status' => 'pending'])],
            ['icon' => 'fa-money-bill-wave', 'color' => 'warning', 'label' => 'مصروفات آجلة متأخرة', 'count' => $overdueExpenses, 'url' => route('expenses.index')],
            ['icon' => 'fa-boxes-stacked', 'color' => 'danger', 'label' => 'أصناف تحت حد المخزون', 'count' => $lowStock, 'url' => route('materials.index', ['low_stock' => 1])],
            ['icon' => 'fa-coins', 'color' => 'info', 'label' => 'أقساط أرباح شركاء مستحقة', 'count' => $duePartnerProfits, 'url' => route('partner_deposits.index')],
            ['icon' => 'fa-file-contract', 'color' => 'secondary', 'label' => 'مستخلصات غير مسدّدة بالكامل', 'count' => $unpaidExtracts, 'url' => route('contractor_extracts.index')],
        ];

        $pendingChangeOrders = \App\Models\ChangeOrder::where('status', 'pending')->count();
        $all[] = ['icon' => 'fa-file-pen', 'color' => 'secondary', 'label' => 'أوامر تغيير بانتظار الاعتماد', 'count' => $pendingChangeOrders, 'url' => route('change_orders.index', ['status' => 'pending'])];

        $openSnags = \App\Models\Snag::where('status', '!=', 'closed')->where('priority', 'high')->count();
        $all[] = ['icon' => 'fa-triangle-exclamation', 'color' => 'danger', 'label' => 'ملاحظات عالية الأولوية مفتوحة', 'count' => $openSnags, 'url' => route('snags.index', ['priority' => 'high'])];

        $overdueRfis = \App\Models\Rfi::where('status', 'open')->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)->count();
        $all[] = ['icon' => 'fa-circle-question', 'color' => 'warning', 'label' => 'طلبات استفسار متأخرة', 'count' => $overdueRfis, 'url' => route('rfis.index', ['status' => 'open'])];

        return array_values(array_filter($all, fn ($a) => $a['count'] > 0));
    }

    public function total(): int
    {
        return array_sum(array_column($this->items(), 'count'));
    }
}
