<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Tender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:tenders.view', only: ['index', 'show']),
            new Middleware('can:tenders.create', only: ['create', 'store']),
            new Middleware('can:tenders.edit', only: ['edit', 'update', 'convertToProject']),
            new Middleware('can:tenders.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $tenders = Tender::query()
            ->with(['client', 'project'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('tender_number', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $won = Tender::where('status', 'won')->count();
        $lost = Tender::where('status', 'lost')->count();
        $decided = $won + $lost;

        $stats = [
            'count' => Tender::count(),
            'submitted' => Tender::where('status', 'submitted')->count(),
            'won' => $won,
            'win_rate' => $decided > 0 ? round($won / $decided * 100, 1) : 0,
            'estimated' => (float) Tender::sum('estimated_value'),
        ];

        return view('tenders.index', compact('tenders', 'search', 'status', 'stats'));
    }

    public function show(Tender $tender): View
    {
        $tender->load(['client', 'project', 'creator']);

        return view('tenders.show', compact('tender'));
    }

    public function create(): View
    {
        return view('tenders.form', $this->formData(
            new Tender([
                'tender_number' => $this->nextNumber(),
                'submission_date' => now()->toDateString(),
                'status' => 'draft',
            ])
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Tender::create($data);

        return redirect()->route('tenders.index')->with('success', 'تمت إضافة المناقصة بنجاح.');
    }

    public function edit(Tender $tender): View
    {
        return view('tenders.form', $this->formData($tender));
    }

    public function update(Request $request, Tender $tender): RedirectResponse
    {
        $tender->update($this->validateData($request, $tender));

        return redirect()->route('tenders.index')->with('success', 'تم تحديث المناقصة.');
    }

    /** تحويل المناقصة الفائزة إلى مشروع جديد وربطها به. */
    public function convertToProject(Request $request, Tender $tender): RedirectResponse
    {
        if ($tender->status !== 'won') {
            return back()->with('error', 'لا يمكن التحويل إلا للمناقصات الفائزة.');
        }
        if ($tender->project_id) {
            return back()->with('error', 'تم تحويل هذه المناقصة إلى مشروع بالفعل.');
        }

        $value = $tender->bid_value ?? $tender->estimated_value ?? 0;

        $project = Project::create([
            'name' => $tender->title,
            'client_id' => $tender->client_id,
            'contract_value' => $value,
            'status' => 'in_progress',
            'start_date' => now()->toDateString(),
            'notes' => 'تم إنشاؤه من المناقصة '.$tender->tender_number,
            'created_by' => $request->user()->id,
        ]);

        $tender->update(['project_id' => $project->id]);

        return redirect()->route('projects.show', $project)->with('success', 'تم تحويل المناقصة إلى مشروع بنجاح.');
    }

    public function destroy(Tender $tender): RedirectResponse
    {
        $tender->delete();

        return back()->with('success', 'تم حذف المناقصة.');
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = Tender::whereYear('created_at', $year)->count() + 1;

        return sprintf('TND-%s-%04d', $year, $count);
    }

    private function formData(Tender $tender): array
    {
        return [
            'tender' => $tender,
            'clients' => Client::orderBy('name')->get(),
            'guarantees' => $this->guarantees(),
        ];
    }

    /** خطابات الضمان من نوع ابتدائي/عطاء (إن وُجد الموديل). */
    private function guarantees(): Collection
    {
        if (! class_exists(\App\Models\LetterOfGuarantee::class)) {
            return collect();
        }

        return \App\Models\LetterOfGuarantee::where('type', 'bid')->orderByDesc('id')->get();
    }

    private function validateData(Request $request, ?Tender $tender = null): array
    {
        return $request->validate([
            'tender_number' => [
                'required', 'string', 'max:50',
                Rule::unique('tenders', 'tender_number')->ignore($tender?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'bid_value' => ['nullable', 'numeric', 'min:0'],
            'submission_date' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys(Tender::STATUSES))],
            'guarantee_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
