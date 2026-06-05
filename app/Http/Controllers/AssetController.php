<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:assets.view', only: ['index', 'show', 'report']),
            new Middleware('can:assets.create', only: ['create', 'store']),
            new Middleware('can:assets.edit', only: ['edit', 'update', 'dispose']),
            new Middleware('can:assets.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $assets = Asset::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('asset_name', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // إحصائيات على كل الأصول النشطة (غير المُباعة/المستبعدة)
        $live = Asset::whereNotIn('status', ['sold', 'disposed'])->get();
        $stats = [
            'count' => Asset::count(),
            'cost' => (float) $live->sum('purchase_value'),
            'accumulated' => (float) $live->reduce(fn ($c, $a) => bcadd($c, $a->accumulatedDepreciation(), 2), '0'),
            'book' => (float) $live->reduce(fn ($c, $a) => bcadd($c, $a->bookValue(), 2), '0'),
        ];

        return view('assets.index', compact('assets', 'search', 'status', 'stats'));
    }

    public function show(Asset $asset): View
    {
        $asset->load('creator');

        return view('assets.show', compact('asset'));
    }

    public function create(): View
    {
        return view('assets.form', [
            'asset' => new Asset(['status' => 'active', 'depreciation_rate' => 10, 'useful_life_years' => 10, 'depreciation_method' => 'straight_line', 'salvage_value' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Asset::create($data);

        return redirect()->route('assets.index')->with('success', 'تمت إضافة الأصل بنجاح.');
    }

    public function edit(Asset $asset): View
    {
        return view('assets.form', ['asset' => $asset]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $asset->update($this->validateData($request, $asset));

        return redirect()->route('assets.index')->with('success', 'تم تحديث الأصل.');
    }

    /** استبعاد/بيع الأصل مع تسجيل تاريخ وقيمة الاستبعاد. */
    public function dispose(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:sold,disposed'],
            'disposal_date' => ['required', 'date'],
            'disposal_value' => ['required', 'numeric', 'min:0'],
        ]);
        $asset->update($data);

        return back()->with('success', 'تم تسجيل استبعاد الأصل. ربح/خسارة الاستبعاد = '.number_format((float) $asset->disposalGainLoss(), 2));
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $asset->delete();

        return back()->with('success', 'تم حذف الأصل.');
    }

    /** تقرير الأصول والإهلاك: التكلفة + مجمّع الإهلاك + القيمة الدفترية + الإجماليات. */
    public function report(Request $request): View|StreamedResponse
    {
        $assets = Asset::orderBy('asset_code')->get();

        $rows = $assets->map(fn (Asset $a) => [
            'code' => $a->asset_code,
            'name' => $a->asset_name,
            'category' => $a->category,
            'purchase_date' => $a->purchase_date?->format('Y-m-d'),
            'purchase_value' => (float) $a->purchase_value,
            'accumulated' => (float) $a->accumulatedDepreciation(),
            'annual' => (float) $a->annualDepreciation(),
            'book' => (float) $a->bookValue(),
            'status' => Asset::STATUSES[$a->status] ?? $a->status,
        ]);

        $totals = [
            'cost' => (float) $assets->sum('purchase_value'),
            'accumulated' => (float) $assets->reduce(fn ($c, $a) => bcadd($c, $a->accumulatedDepreciation(), 2), '0'),
            'book' => (float) $assets->reduce(fn ($c, $a) => bcadd($c, $a->bookValue(), 2), '0'),
        ];

        if ($request->input('export') === 'xlsx') {
            $headers = ['الكود', 'الأصل', 'الفئة', 'تاريخ الشراء', 'التكلفة', 'مجمّع الإهلاك', 'القسط السنوي', 'القيمة الدفترية', 'الحالة'];
            $data = $rows->map(fn ($r) => array_values($r))->all();

            return app(ExportService::class)->excel($headers, $data, 'assets-depreciation', 'تقرير الأصول والإهلاك');
        }

        return view('assets.report', compact('rows', 'totals'));
    }

    private function validateData(Request $request, ?Asset $asset = null): array
    {
        return $request->validate([
            'asset_code' => ['required', 'string', 'max:50', Rule::unique('assets', 'asset_code')->ignore($asset)],
            'asset_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'purchase_value' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'depreciation_method' => ['required', 'in:'.implode(',', array_keys(Asset::METHODS))],
            'depreciation_rate' => ['required', 'numeric', 'min:0'],
            'useful_life_years' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:'.implode(',', array_keys(Asset::STATUSES))],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
