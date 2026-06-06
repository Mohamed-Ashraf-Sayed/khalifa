<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class JournalEntryController extends Controller implements HasMiddleware
{
    public function __construct(private JournalService $journal)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('can:accounting.view', only: ['index', 'show']),
            new Middleware('can:accounting.create', only: ['create', 'store']),
            new Middleware('can:accounting.edit', only: ['edit', 'update', 'post', 'unpost']),
            new Middleware('can:accounting.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        $from = (string) $request->input('from', '');
        $to = (string) $request->input('to', '');

        $entries = JournalEntry::query()
            ->with('creator')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($from !== '', fn ($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('entry_date', '<=', $to))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'count' => JournalEntry::count(),
            'drafts' => JournalEntry::where('status', 'draft')->count(),
            'posted' => JournalEntry::where('status', 'posted')->count(),
            'posted_debit' => (float) JournalEntry::where('status', 'posted')->sum('total_debit'),
        ];

        return view('journal_entries.index', compact('entries', 'search', 'status', 'from', 'to', 'stats'));
    }

    public function show(JournalEntry $journalEntry): View
    {
        $journalEntry->load(['lines.account', 'lines.project', 'lines.costCenter', 'creator', 'poster']);

        return view('journal_entries.show', ['entry' => $journalEntry]);
    }

    public function create(): View
    {
        return view('journal_entries.form', $this->formData(
            new JournalEntry([
                'entry_date' => now()->toDateString(),
                'status' => 'draft',
            ])
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        try {
            $entry = $this->journal->createEntry(
                [
                    'entry_date' => $data['entry_date'],
                    'description' => $data['description'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ],
                $this->buildLines($data['lines'] ?? [])
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('journal_entries.show', $entry)->with('success', 'تم إنشاء القيد بنجاح.');
    }

    public function edit(JournalEntry $journalEntry): RedirectResponse|View
    {
        if ($journalEntry->status !== 'draft') {
            return redirect()->route('journal_entries.show', $journalEntry)
                ->with('error', 'لا يمكن تعديل قيد مرحّل. ألغِ الترحيل أولاً.');
        }

        $journalEntry->load('lines');

        return view('journal_entries.form', $this->formData($journalEntry));
    }

    public function update(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        if ($journalEntry->status !== 'draft') {
            return back()->with('error', 'لا يمكن تعديل قيد مرحّل. ألغِ الترحيل أولاً.');
        }

        $data = $this->validateData($request);

        try {
            // إعادة بناء القيد: نحذف القديم وننشئ بنفس المنطق المتوازن
            $entry = \Illuminate\Support\Facades\DB::transaction(function () use ($journalEntry, $data, $request) {
                $ref = [
                    'source' => $journalEntry->source,
                    'reference_type' => $journalEntry->reference_type,
                    'reference_id' => $journalEntry->reference_id,
                ];
                $journalEntry->lines()->delete();
                $journalEntry->delete();

                return $this->journal->createEntry(
                    [
                        'entry_date' => $data['entry_date'],
                        'description' => $data['description'],
                        'notes' => $data['notes'] ?? null,
                        'created_by' => $request->user()->id,
                    ],
                    $this->buildLines($data['lines'] ?? []),
                    $ref
                );
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('journal_entries.show', $entry)->with('success', 'تم تحديث القيد.');
    }

    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        if ($journalEntry->status !== 'draft') {
            return back()->with('error', 'لا يمكن حذف قيد مرحّل. ألغِ الترحيل أولاً.');
        }

        $journalEntry->delete();

        return redirect()->route('journal_entries.index')->with('success', 'تم حذف القيد.');
    }

    public function post(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        if ($journalEntry->status !== 'draft') {
            return back()->with('error', 'القيد مرحّل بالفعل.');
        }

        if (! $journalEntry->isBalanced()) {
            return back()->with('error', 'لا يمكن ترحيل قيد غير متوازن.');
        }

        $this->journal->post($journalEntry, $request->user()->id);

        return back()->with('success', 'تم ترحيل القيد.');
    }

    public function unpost(JournalEntry $journalEntry): RedirectResponse
    {
        if ($journalEntry->status !== 'posted') {
            return back()->with('error', 'القيد ليس مرحّلاً.');
        }

        $this->journal->unpost($journalEntry);

        return back()->with('success', 'تم إلغاء ترحيل القيد.');
    }

    /** تحويل بنود الـ request إلى مصفوفة بنود نظيفة (نتجاهل الصفوف الفارغة تماماً). */
    private function buildLines(array $rows): array
    {
        $lines = [];

        foreach ($rows as $row) {
            $accountId = $row['account_id'] ?? null;
            $debit = (string) ($row['debit'] ?? 0);
            $credit = (string) ($row['credit'] ?? 0);

            if (! $accountId) {
                continue;
            }

            // تجاهل الصفوف اللي مفيهاش أي قيمة
            if (bccomp($debit === '' ? '0' : $debit, '0', 2) <= 0 && bccomp($credit === '' ? '0' : $credit, '0', 2) <= 0) {
                continue;
            }

            $lines[] = [
                'account_id' => (int) $accountId,
                'debit' => $debit === '' ? 0 : $debit,
                'credit' => $credit === '' ? 0 : $credit,
                'description' => $row['description'] ?? null,
                'project_id' => $row['project_id'] ?? null,
                'cost_center_id' => $row['cost_center_id'] ?? null,
            ];
        }

        return $lines;
    }

    private function formData(JournalEntry $journalEntry): array
    {
        return [
            'entry' => $journalEntry,
            'accounts' => Account::query()
                ->where('is_group', false)
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
            'projects' => Project::orderBy('name')->get(),
            'costCenters' => CostCenter::where('is_active', true)->orderBy('name')->get(),
            'seedLines' => $this->seedLines($journalEntry),
        ];
    }

    /** بنود مبدئية للفورم (للتعديل): array بسيط بدون مدين/دائن صفرية فارغة. */
    private function seedLines(JournalEntry $journalEntry): array
    {
        if (! $journalEntry->exists) {
            return [];
        }

        return $journalEntry->lines->map(fn ($l) => [
            'account_id' => $l->account_id,
            'debit' => bccomp((string) $l->debit, '0', 2) > 0 ? (float) $l->debit : '',
            'credit' => bccomp((string) $l->credit, '0', 2) > 0 ? (float) $l->credit : '',
            'project_id' => $l->project_id,
            'cost_center_id' => $l->cost_center_id,
            'description' => $l->description,
        ])->values()->all();
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['nullable', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.project_id' => ['nullable', 'exists:projects,id'],
            'lines.*.cost_center_id' => ['nullable', 'exists:cost_centers,id'],
        ]);
    }
}
