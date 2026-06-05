<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\EquipmentLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EquipmentLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:assets.view', only: ['index']),
            new Middleware('can:assets.edit', only: ['store', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $assetId = (string) $request->input('asset_id', '');
        $logType = (string) $request->input('log_type', '');

        $logs = EquipmentLog::query()
            ->with('asset')
            ->when($assetId !== '', fn ($q) => $q->where('asset_id', $assetId))
            ->when($logType !== '', fn ($q) => $q->where('log_type', $logType))
            ->latest('log_date')
            ->paginate(20)
            ->withQueryString();

        $maintenanceCost = (float) EquipmentLog::where('log_type', 'maintenance')
            ->when($assetId !== '', fn ($q) => $q->where('asset_id', $assetId))
            ->get()
            ->reduce(fn ($c, $l) => bcadd($c, (string) ($l->cost ?? 0), 2), '0');

        $assets = Asset::orderBy('asset_name')->get(['id', 'asset_name']);

        return view('equipment_logs.index', compact('logs', 'assets', 'assetId', 'logType', 'maintenanceCost'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'log_type' => ['required', 'in:'.implode(',', array_keys(EquipmentLog::LOG_TYPES))],
            'log_date' => ['required', 'date'],
            'operating_hours' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'next_service_date' => ['nullable', 'date'],
        ]);
        $data['created_by'] = $request->user()->id;
        EquipmentLog::create($data);

        return back()->with('success', 'تم تسجيل العملية على المعدة بنجاح.');
    }

    public function destroy(EquipmentLog $equipmentLog): RedirectResponse
    {
        $equipmentLog->delete();

        return back()->with('success', 'تم حذف السجل.');
    }
}
