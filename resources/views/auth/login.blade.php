<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#2b4c80; --navy-d:#1f3a63; --navy-dd:#14264a; --ink:#2f2a22; --muted:#938974; --line:#e4e8f0; }
        * { font-family: 'Cairo', sans-serif; box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--navy-dd); }

        /* ===== تقسيم احترافي: الفورم يمين والصورة شمال (RTL) ===== */
        .split { display: flex; min-height: 100vh; }

        .split-form {
            flex: 1 1 44%; display: flex; align-items: center; justify-content: center;
            background: #fff; padding: 2.5rem 2rem; position: relative; z-index: 2;
            box-shadow: -14px 0 44px rgba(20,38,74,.13);
        }
        .form-wrap { width: 100%; max-width: 380px; }

        .split-image {
            flex: 1 1 56%; position: relative;
            background: linear-gradient(115deg, rgba(20,38,74,.08), rgba(20,38,74,.28)),
                        url('/images/login-bg.png') center center / cover no-repeat;
        }
        .split-image::after { content: ''; position: absolute; inset: 0; box-shadow: inset 0 0 130px rgba(20,38,74,.16); }

        /* ===== ترويسة النموذج ===== */
        .brand-badge { width: 84px; height: 84px; border-radius: 22px; margin: 0 auto 1.1rem;
            background: #fff; color: var(--ink); display: flex; align-items: center; justify-content: center; padding: 13px;
            box-shadow: 0 14px 30px rgba(43,76,128,.20); border: 1px solid #eef0f4; }
        .brand-badge .app-logo { width: 100%; height: 100%; object-fit: contain; }
        .form-wrap h4 { font-weight: 800; color: var(--ink); margin: 0; text-align: center; letter-spacing: -.01em; font-size: 1.5rem; }
        .form-wrap .sub { color: var(--muted); text-align: center; margin: .3rem 0 2rem; font-size: .9rem; }

        /* ===== حقول عصرية بإطار موحّد ===== */
        .field { margin-bottom: 1.15rem; }
        .field > label { font-weight: 600; font-size: .82rem; color: #3a4452; display: block; margin-bottom: .4rem; }
        .ig { display: flex; align-items: center; border: 1px solid var(--line); border-radius: .85rem;
            background: #fbfcfe; transition: border-color .15s, box-shadow .15s, background .15s; overflow: hidden; }
        .ig:focus-within { border-color: #5b7bab; box-shadow: 0 0 0 .22rem rgba(43,76,128,.13); background: #fff; }
        .ig .ico { flex: 0 0 auto; width: 46px; display: flex; align-items: center; justify-content: center; color: #9aa3b2; font-size: .95rem; }
        .ig input { flex: 1 1 auto; border: 0; outline: 0; background: transparent; padding: .72rem .2rem; font-size: .95rem; color: var(--ink); min-width: 0; }
        .ig input::placeholder { color: #b9c0cc; }
        .ig .toggle { flex: 0 0 auto; width: 46px; background: transparent; border: 0; color: #9aa3b2; cursor: pointer; }
        .ig .toggle:hover { color: var(--navy); }

        .check-row { display: flex; align-items: center; gap: .5rem; margin: .2rem 0 1.4rem; }
        .check-row input { width: 1.05rem; height: 1.05rem; accent-color: var(--navy); }
        .check-row label { font-size: .86rem; color: #4a5260; cursor: pointer; }

        .btn-login { width: 100%; border: 0; border-radius: .85rem; padding: .8rem; font-weight: 700; font-size: 1rem; color: #fff;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-d) 100%);
            box-shadow: 0 8px 20px rgba(43,76,128,.28); transition: transform .15s, box-shadow .15s, filter .15s; }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 12px 26px rgba(43,76,128,.36); filter: brightness(1.05); }
        .btn-login:active { transform: translateY(0); }

        .err { background: #fdecea; color: #8f3f3b; border-radius: .8rem; padding: .65rem .9rem; font-size: .86rem; margin-bottom: 1.1rem; }
        .form-foot { color: #aab0bb; font-size: .76rem; text-align: center; margin-top: 1.8rem; }

        /* ===== موبايل: كارت أبيض راقٍ فوق خلفية البراند ===== */
        @media (max-width: 860px) {
            .split { align-items: center; justify-content: center; padding: 1.4rem;
                background: linear-gradient(160deg, rgba(31,58,99,.62), rgba(20,38,74,.74)), url('/images/login-bg.png') center center / cover no-repeat fixed; }
            .split-image { display: none; }
            .split-form { flex: 0 0 auto; width: 100%; max-width: 430px; border-radius: 22px;
                padding: 2.4rem 1.7rem; box-shadow: 0 24px 60px rgba(10,20,45,.45); }
        }
    </style>
</head>
<body>
    <div class="split">
        {{-- يمين: نموذج الدخول --}}
        <div class="split-form">
            <div class="form-wrap">
                <div class="brand-badge">@include('partials.logo')</div>
                <h4>{{ config('app.name') }}</h4>
                <p class="sub">سجّل دخولك للمتابعة</p>

                @if ($errors->any())
                    <div class="err"><i class="fa-solid fa-circle-exclamation ms-1"></i> {{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="field">
                        <label>البريد الإلكتروني</label>
                        <div class="ig">
                            <span class="ico"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@example.com">
                        </div>
                    </div>
                    <div class="field">
                        <label>كلمة المرور</label>
                        <div class="ig">
                            <span class="ico"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" id="pw" required placeholder="••••••••">
                            <button type="button" class="toggle" onclick="const p=document.getElementById('pw');p.type=p.type==='password'?'text':'password';this.querySelector('i').classList.toggle('fa-eye');this.querySelector('i').classList.toggle('fa-eye-slash')"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="check-row">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">تذكّرني على هذا الجهاز</label>
                    </div>
                    <button type="submit" class="btn-login">
                        <i class="fa-solid fa-right-to-bracket ms-1"></i> تسجيل الدخول
                    </button>
                </form>

                <div class="form-foot">© {{ date('Y') }} {{ config('app.name') }} — جميع الحقوق محفوظة</div>
            </div>
        </div>

        {{-- شمال: صورة البراند --}}
        <div class="split-image" aria-hidden="true"></div>
    </div>
</body>
</html>
