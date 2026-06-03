<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorController extends Controller
{
    /**
     * شاشة تحدي المصادقة الثنائية. متاحة فقط لمن اجتاز كلمة المرور (session 2fa_user_id).
     */
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * التحقق من رمز 2FA وإتمام تسجيل الدخول مع كل التسجيلات (audit).
     */
    public function verify(Request $request): RedirectResponse
    {
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = User::find($request->session()->get('2fa_user_id'));
        if (! $user) {
            $request->session()->forget(['2fa_user_id', '2fa_remember']);

            return redirect()->route('login');
        }

        $google2fa = new Google2FA;
        if (! $user->two_factor_secret || ! $google2fa->verifyKey($user->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => 'الرمز غير صحيح. أعد المحاولة.']);
        }

        $remember = (bool) $request->session()->get('2fa_remember', false);
        Auth::loginUsingId($user->id, $remember);
        $request->session()->regenerate(); // يمنع session fixation

        // تسجيل دخول ناجح (audit) — بعد اجتياز 2FA
        LoginAttempt::create([
            'email' => $user->email,
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'successful' => true,
        ]);
        ActivityLog::record('login', $user);

        // تسجيل آخر دخول ناجح
        $user->forceFill(['last_login_at' => now()])->save();

        $request->session()->forget(['2fa_user_id', '2fa_remember']);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * إلغاء التحدي والعودة لشاشة الدخول.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['2fa_user_id', '2fa_remember']);

        return redirect()->route('login');
    }
}
