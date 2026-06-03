<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // حماية brute-force: 5 محاولات لكل (إيميل + IP) في الدقيقة
        $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "محاولات كثيرة. حاول مرة أخرى بعد {$seconds} ثانية.",
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            // تسجيل محاولة دخول فاشلة (audit)
            LoginAttempt::create([
                'email' => $credentials['email'],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'successful' => false,
            ]);
            // رسالة عامة — مش بنكشف إذا كان الإيميل موجود أو لا
            throw ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        // منع دخول الحسابات المعطّلة
        if (! Auth::user()->is_active) {
            // تسجيل محاولة دخول فاشلة لحساب معطّل (audit) قبل الخروج
            LoginAttempt::create([
                'email' => $credentials['email'],
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'successful' => false,
            ]);
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'هذا الحساب معطّل. تواصل مع المدير.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate(); // يمنع session fixation

        // المصادقة الثنائية: ما نكملش الدخول — نوقف المستخدم ونحوّله لشاشة التحدي.
        // التسجيل (last_login / LoginAttempt success / ActivityLog) يتم بعد نجاح 2FA فقط.
        if (Auth::user()->two_factor_enabled) {
            $request->session()->put('2fa_user_id', Auth::id());
            $request->session()->put('2fa_remember', $request->boolean('remember'));
            Auth::logout(); // لسه مش مسجّل دخول بالكامل

            return redirect()->route('two_factor.challenge');
        }

        // تسجيل دخول ناجح (audit)
        LoginAttempt::create([
            'email' => $credentials['email'],
            'user_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'successful' => true,
        ]);
        ActivityLog::record('login', Auth::user());

        // تسجيل آخر دخول ناجح
        Auth::user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // تسجيل خروج (audit) قبل إنهاء الجلسة
        if ($user = $request->user()) {
            ActivityLog::record('logout', $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
