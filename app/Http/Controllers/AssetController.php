<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:assets.view', only: ['index', 'show']),
            new Middleware('can:assets.create', only: ['create', 'store']),
            new Middleware('can:assets.edit', only: ['edit', 'update']),
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

        return view('assets.index', compact('assets', 'search', 'status'));
    }

    public function show(Asset $asset): View
    {
        $asset->load('creator');

        return view('assets.show', compact('asset'));
    }

    public function create(): View
    {
        return view('assets.form', [
            'asset' => new Asset(['status' => 'active', 'depreciation_rate' => 10, 'useful_life_years' => 10]),
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
        return view('assets.form', [
            'asset' => $asset,
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $asset->update($this->validateData($request, $asset));

        return redirect()->route('assets.index')->with('success', 'تم تحديث الأصل.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $asset->delete();

        return back()->with('success', 'تم حذف الأصل.');
    }

    private function validateData(Request $request, ?Asset $asset = null): array
    {
        return $request->validate([
            'asset_code' => ['required', 'string', 'max:50', Rule::unique('assets', 'asset_code')->ignore($asset)],
            'asset_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'purchase_value' => ['required', 'numeric', 'min:0'],
            'depreciation_rate' => ['required', 'numeric', 'min:0'],
            'useful_life_years' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:'.implode(',', array_keys(Asset::STATUSES))],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
