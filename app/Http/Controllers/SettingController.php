<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SettingController extends Controller implements HasMiddleware
{
    private const KEYS = ['site_name', 'company_name', 'company_phone', 'company_email', 'company_address', 'commercial_register', 'tax_number', 'legal_form'];

    /** الإعدادات الإضافية: المفتاح => القيمة الافتراضية. */
    private const EXTRA_KEYS = [
        'tax_rate' => '14',
        'retention_rate' => '10',
        'insurance_rate' => '1',
        'currency' => 'ج.م',
        'timezone' => 'Africa/Cairo',
        'low_stock_alert' => '1',
        'payment_reminder_days' => '',
    ];

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
        foreach (self::EXTRA_KEYS as $k => $default) {
            $settings[$k] = Setting::get($k, $default);
        }

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver' => config('database.default'),
            'timezone' => config('app.timezone'),
        ];

        $counts = [
            'users' => User::count(),
            'projects' => Project::count(),
            'clients' => Client::count(),
            'contractors' => Contractor::count(),
            'suppliers' => Supplier::count(),
            'employees' => Employee::count(),
        ];

        return view('settings.edit', compact('settings', 'systemInfo', 'counts'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'legal_form' => ['nullable', 'string', 'max:100'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'retention_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'insurance_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'low_stock_alert' => ['nullable', 'boolean'],
            'payment_reminder_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        foreach (self::KEYS as $k) {
            Setting::put($k, $data[$k] ?? null);
        }

        foreach (array_keys(self::EXTRA_KEYS) as $k) {
            if ($k === 'low_stock_alert') {
                Setting::put($k, $request->boolean('low_stock_alert') ? '1' : '0');

                continue;
            }
            Setting::put($k, $data[$k] ?? null);
        }

        return back()->with('success', 'تم حفظ الإعدادات.');
    }
}
