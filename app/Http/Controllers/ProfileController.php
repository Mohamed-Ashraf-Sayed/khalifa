<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use PragmaRX\Google2FAQRCode\Google2FA;

class ProfileController extends Controller
{
    // ملاحظة: لا حاجة لـ can: middleware — مجموعة المسارات محمية بـ auth،
    // وأي مستخدم مسجّل يقدر يعدّل ملفه الشخصي.

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);
        $user->save();

        return back()->with('success', 'تم تحديث البيانات الشخصية.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();
        $user->password = $data['password']; // يتشفّر عبر cast
        $user->save();

        return back()->with('success', 'تم تغيير كلمة المرور.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ]);

        $user = $request->user();

        // حذف الصورة القديمة إن وُجدت
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return back()->with('success', 'تم تحديث الصورة الشخصية.');
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = null;
        $user->save();

        return back()->with('success', 'تم حذف الصورة الشخصية.');
    }

    /**
     * بدء تفعيل المصادقة الثنائية: توليد سر مؤقت + رمز QR للمسح.
     * السر يُخزَّن مؤقتاً في الجلسة لحين تأكيد المستخدم برمز صحيح.
     */
    public function twoFactorEnable(Request $request): RedirectResponse
    {
        $user = $request->user();
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $request->session()->put('2fa_pending_secret', $secret);

        $qr = $google2fa->getQRCodeInline(config('app.name'), $user->email, $secret);

        return back()
            ->with('2fa_secret', $secret)
            ->with('2fa_qr', $qr);
    }

    /**
     * تأكيد المصادقة الثنائية: التحقق من الرمز مقابل السر المؤقت ثم حفظه.
     */
    public function twoFactorConfirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = $request->session()->get('2fa_pending_secret');
        if (! $secret) {
            return back()->with('error', 'انتهت صلاحية الجلسة. أعد المحاولة.');
        }

        $google2fa = new Google2FA;
        if (! $google2fa->verifyKey($secret, $data['code'])) {
            return back()
                ->with('error', 'الرمز غير صحيح. أعد المحاولة.')
                ->with('2fa_secret', $secret)
                ->with('2fa_qr', $google2fa->getQRCodeInline(config('app.name'), $request->user()->email, $secret));
        }

        $user = $request->user();
        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = true;
        $user->save();

        $request->session()->forget('2fa_pending_secret');

        return back()->with('success', 'تم تفعيل المصادقة الثنائية.');
    }

    /**
     * تعطيل المصادقة الثنائية: يتطلب كلمة المرور الحالية.
     */
    public function twoFactorDisable(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->save();

        return back()->with('success', 'تم تعطيل المصادقة الثنائية.');
    }
}
