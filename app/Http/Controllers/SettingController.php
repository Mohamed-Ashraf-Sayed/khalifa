<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SettingController extends Controller implements HasMiddleware
{
    private const KEYS = ['site_name', 'company_name', 'company_phone', 'company_email', 'company_address'];

    public static function middleware(): array
    {
        return [
            new Middleware('can:settings.view', only: ['edit']),
            new Middleware('can:settings.edit', only: ['update']),
        ];
    }

    public function edit(): View
    {
        $settings = [];
        foreach (self::KEYS as $k) {
            $settings[$k] = Setting::get($k, '');
        }

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
        ]);

        foreach (self::KEYS as $k) {
            Setting::put($k, $data[$k] ?? null);
        }

        return back()->with('success', 'تم حفظ الإعدادات.');
    }
}
