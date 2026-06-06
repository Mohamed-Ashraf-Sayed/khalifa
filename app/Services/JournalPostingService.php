<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ContractorExtract;
use App\Models\ContractorPayment;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\SupplierPayment;
use App\Models\SupplierTransaction;
use Illuminate\Database\Eloquent\Model;

/**
 * الترحيل المحاسبي التلقائي: يولّد قيود يومية متوازنة من المستندات التشغيلية.
 *
 * مبادئ تمنع الازدواج المحاسبي:
 *  - كل حدث اقتصادي يُرحَّل مرّة واحدة فقط (idempotent عبر reference_type + reference_id).
 *  - الاعتراف بالإيراد من الفواتير، والتحصيل من دفعات الفواتير (مش من الإيرادات والبنوك معاً).
 *  - الجانب النقدي يأتي من مستندات الدفع/التحصيل؛ التحويلات البنكية بين حسابات الشركة لا تُرحَّل (تتعادل).
 *  - كل القيم bcmath، وكل قيد متوازن (يرفضه JournalService لو مش متوازن).
 */
class JournalPostingService
{
    /** أنواع المستندات المدعومة + عناوينها. */
    public const DOC_TYPES = [
        'invoice' => 'الفواتير (اعتراف بالإيراد)',
        'invoice_payment' => 'تحصيلات الفواتير',
        'expense' => 'المصروفات',
        'expense_payment' => 'سداد المصروفات الآجلة',
        'contractor_extract' => 'مستخلصات المقاولين',
        'contractor_payment' => 'دفعات المقاولين',
        'supplier_transaction' => 'مشتريات الموردين',
        'supplier_payment' => 'مدفوعات الموردين',
        'payroll_run' => 'مسيّرات الرواتب',
    ];

    public function __construct(private JournalService $journal) {}

    /** خريطة فئة المصروف → كود حساب المصروف. */
    private const EXPENSE_CATEGORY_ACCOUNT = [
        'materials' => 'material_cost',
        'labor' => 'labor_cost',
        'equipment' => 'equipment_cost',
        'transportation' => '5104',
        'utilities' => '5203',
        'administrative' => 'general_expense',
        'other' => 'general_expense',
    ];

    /**
     * يولّد قيوداً لكل المستندات غير المُرحّلة.
     *
     * @return array<string, array{posted:int,skipped:int}>
     */
    public function generateAll(?int $userId = null): array
    {
        $result = [];
        foreach (array_keys(self::DOC_TYPES) as $type) {
            $result[$type] = $this->generateForType($type, $userId);
        }

        return $result;
    }

    /** يولّد قيوداً لنوع مستند واحد. */
    public function generateForType(string $type, ?int $userId = null): array
    {
        $posted = 0;
        $skipped = 0;

        foreach ($this->documents($type) as $doc) {
            $entry = $this->postDocument($type, $doc, $userId);
            $entry ? $posted++ : $skipped++;
        }

        return ['posted' => $posted, 'skipped' => $skipped];
    }

    /** عدّادات لمركز الترحيل: إجمالي المستندات المؤهّلة مقابل المُرحَّل منها. */
    public function counts(): array
    {
        $out = [];
        foreach (array_keys(self::DOC_TYPES) as $type) {
            $total = $this->documents($type)->count();
            $posted = JournalEntry::where('reference_type', $type)->count();
            $out[$type] = ['label' => self::DOC_TYPES[$type], 'total' => $total, 'posted' => $posted];
        }

        return $out;
    }

    /** مجموعة المستندات المؤهّلة للترحيل حسب النوع. */
    private function documents(string $type)
    {
        return match ($type) {
            'invoice' => Invoice::whereNotIn('status', ['draft', 'cancelled'])->get(),
            'invoice_payment' => InvoicePayment::all(),
            'expense' => Expense::all(),
            'expense_payment' => ExpensePayment::all(),
            'contractor_extract' => ContractorExtract::whereIn('status', ['approved', 'partial', 'paid'])->get(),
            'contractor_payment' => ContractorPayment::all(),
            'supplier_transaction' => SupplierTransaction::all(),
            'supplier_payment' => SupplierPayment::all(),
            'payroll_run' => PayrollRun::where('status', 'paid')->get(),
            default => collect(),
        };
    }

    /**
     * يرحّل مستنداً واحداً (idempotent). يرجّع القيد أو null لو موجود/غير صالح.
     */
    public function postDocument(string $type, Model $doc, ?int $userId = null): ?JournalEntry
    {
        // عدم التكرار: لو فيه قيد سابق لنفس المرجع، تخطَّ
        if (JournalEntry::where('reference_type', $type)->where('reference_id', $doc->getKey())->exists()) {
            return null;
        }

        [$date, $desc, $lines] = $this->build($type, $doc);

        if (! $lines || count($lines) < 2) {
            return null;
        }

        // تأكّد أن كل البنود لها حساب مُعرّف (الشجرة مزروعة)
        foreach ($lines as $l) {
            if (empty($l['account_id'])) {
                return null;
            }
        }

        $entry = $this->journal->createEntry(
            ['entry_date' => $date, 'description' => $desc, 'created_by' => $userId],
            $lines,
            ['source' => 'auto', 'reference_type' => $type, 'reference_id' => $doc->getKey()],
        );

        return $this->journal->post($entry, $userId);
    }

    /** يبني (التاريخ، الوصف، البنود) لكل نوع مستند. */
    private function build(string $type, Model $doc): array
    {
        return match ($type) {
            'invoice' => $this->buildInvoice($doc),
            'invoice_payment' => $this->buildInvoicePayment($doc),
            'expense' => $this->buildExpense($doc),
            'expense_payment' => $this->buildExpensePayment($doc),
            'contractor_extract' => $this->buildContractorExtract($doc),
            'contractor_payment' => $this->buildContractorPayment($doc),
            'supplier_transaction' => $this->buildSupplierTransaction($doc),
            'supplier_payment' => $this->buildSupplierPayment($doc),
            'payroll_run' => $this->buildPayrollRun($doc),
            default => [null, '', []],
        };
    }

    private function id(string $key): ?int
    {
        return Account::resolve($key)?->id;
    }

    private function dr(string $key, string $amount, ?int $projectId = null, ?string $desc = null): array
    {
        return ['account_id' => $this->id($key), 'debit' => $amount, 'credit' => 0, 'project_id' => $projectId, 'description' => $desc];
    }

    private function cr(string $key, string $amount, ?int $projectId = null, ?string $desc = null): array
    {
        return ['account_id' => $this->id($key), 'debit' => 0, 'credit' => $amount, 'project_id' => $projectId, 'description' => $desc];
    }

    private function buildInvoice(Invoice $inv): array
    {
        $total = (string) $inv->total_amount;
        $subtotal = (string) $inv->subtotal;
        $tax = (string) $inv->tax_amount;
        $lines = [
            $this->dr('ar', $total, $inv->project_id, 'فاتورة '.$inv->invoice_number),
            $this->cr('contract_revenue', $subtotal, $inv->project_id, 'إيراد فاتورة '.$inv->invoice_number),
        ];
        if (bccomp($tax, '0', 2) > 0) {
            $lines[] = $this->cr('vat_output', $tax, $inv->project_id, 'ضريبة قيمة مضافة');
        }

        return [$inv->issue_date?->toDateString(), 'اعتراف بإيراد الفاتورة '.$inv->invoice_number, $lines];
    }

    private function buildInvoicePayment(InvoicePayment $p): array
    {
        $amt = (string) $p->amount;
        $cash = $p->payment_method === 'cash' ? 'cash' : 'bank';

        return [$p->payment_date?->toDateString(), 'تحصيل دفعة فاتورة', [
            $this->dr($cash, $amt, null, 'تحصيل دفعة'),
            $this->cr('ar', $amt, null, 'تخفيض ذمم العملاء'),
        ]];
    }

    private function buildExpense(Expense $e): array
    {
        $amt = (string) $e->amount;
        $expenseKey = self::EXPENSE_CATEGORY_ACCOUNT[$e->category] ?? 'general_expense';
        // الجانب الدائن: آجل → الموردون، نقدي → الخزينة/البنك
        $creditKey = $e->is_credit ? 'ap' : ($e->payment_method === 'cash' ? 'cash' : 'bank');

        return [$e->expense_date?->toDateString(), 'مصروف: '.$e->description, [
            $this->dr($expenseKey, $amt, $e->project_id, $e->description),
            $this->cr($creditKey, $amt, $e->project_id, $e->is_credit ? 'التزام آجل' : 'سداد نقدي'),
        ]];
    }

    private function buildExpensePayment(ExpensePayment $p): array
    {
        $amt = (string) $p->amount;
        $cash = $p->payment_method === 'cash' ? 'cash' : 'bank';

        return [$p->payment_date?->toDateString(), 'سداد مصروف آجل', [
            $this->dr('ap', $amt, null, 'تخفيض التزام'),
            $this->cr($cash, $amt, null, 'سداد نقدي'),
        ]];
    }

    private function buildContractorExtract(ContractorExtract $x): array
    {
        $net = (string) $x->net_amount;
        $retention = (string) ($x->retention_amount ?? 0);
        $payable = bcsub($net, $retention, 2);
        $lines = [$this->dr('contractor_cost', $net, $x->project_id, 'مستخلص '.$x->extract_number)];
        if (bccomp($retention, '0', 2) > 0) {
            $lines[] = $this->cr('retention', $retention, $x->project_id, 'محتجز ضمان أعمال');
        }
        $lines[] = $this->cr('subcontractors', $payable, $x->project_id, 'مستحق للمقاول');

        return [$x->extract_date?->toDateString(), 'مستخلص مقاول '.$x->extract_number, $lines];
    }

    private function buildContractorPayment(ContractorPayment $p): array
    {
        $amt = (string) $p->amount;
        $cash = $p->payment_method === 'cash' ? 'cash' : 'bank';

        return [$p->payment_date?->toDateString(), 'دفعة مقاول', [
            $this->dr('subcontractors', $amt, null, 'سداد للمقاول'),
            $this->cr($cash, $amt, null, 'صرف نقدي'),
        ]];
    }

    private function buildSupplierTransaction(SupplierTransaction $t): array
    {
        $net = (string) $t->net_amount;

        return [$t->transaction_date?->toDateString(), 'مشتريات مورّد: '.$t->item_description, [
            $this->dr('material_cost', $net, $t->project_id, $t->item_description),
            $this->cr('ap', $net, $t->project_id, 'مستحق للمورّد'),
        ]];
    }

    private function buildSupplierPayment(SupplierPayment $p): array
    {
        $amt = (string) $p->amount;
        $cash = $p->payment_method === 'cash' ? 'cash' : 'bank';

        return [$p->payment_date?->toDateString(), 'دفعة مورّد', [
            $this->dr('ap', $amt, null, 'تخفيض مستحقات المورّد'),
            $this->cr($cash, $amt, null, 'صرف نقدي'),
        ]];
    }

    private function buildPayrollRun(PayrollRun $r): array
    {
        $net = (string) $r->total_net;

        return [optional($r->paid_at)->toDateString() ?? now()->toDateString(), 'رواتب '.$r->run_number, [
            $this->dr('salaries_expense', $net, null, 'مصروف رواتب '.$r->run_number),
            $this->cr('bank', $net, null, 'صرف رواتب'),
        ]];
    }
}
