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
        * { font-family: 'Cairo', sans-serif; box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #14264a; }

        /* تقسيم الصفحة: الفورم يمين والصورة شمال (RTL) */
        .split { display: flex; min-height: 100vh; }

        /* الجهة اليمنى — نموذج الدخول */
        .split-form {
            flex: 1 1 44%; display: flex; align-items: center; justify-content: center;
            background: #fff; padding: 2.5rem 2rem; position: relative; z-index: 2;
            box-shadow: -12px 0 40px rgba(20,38,74,.12);
        }
        .form-wrap { width: 100%; max-width: 390px; }

        /* الجهة الشمال — صورة البراند */
        .split-image {
            flex: 1 1 56%; position: relative;
            background: linear-gradient(115deg, rgba(20,38,74,.10) 0%, rgba(20,38,74,.30) 100%),
                        url('/images/login-bg.png') center center / cover no-repeat;
        }
        .split-image::after {
            content: ''; position: absolute; inset: 0;
            box-shadow: inset 0 0 120px rgba(20,38,74,.18);
        }

        /* ترويسة النموذج */
        .brand-badge { width: 88px; height: 88px; border-radius: 22px; margin: 0 auto 1rem;
            background: #fff; color: #2f2a22; display: flex; align-items: center; justify-content: center; padding: 13px;
            box-shadow: 0 12px 28px rgba(43,76,128,.22); border: 1px solid #ece5d8; }
        .brand-badge .app-logo { width: 100%; height: 100%; object-fit: contain; }
        .form-wrap h4 { font-weight: 800; color: #2f2a22; margin: 0; text-align: center; letter-spacing: -.01em; }
        .form-wrap .sub { color: #938974; text-align: center; margin: .25rem 0 1.8rem; font-size: .9rem; }

        .form-label { font-weight: 600; font-size: .85rem; color: #3a4452; }
        .form-control { border-radius: .7rem; border-color: #ece5d8; padding: .62rem .9rem; background: #fbfcfe; }
        .form-control:focus { border-color: #5b7bab; box-shadow: 0 0 0 .2rem rgba(43,76,128,.15); background: #fff; }
        .input-group-text { background: #f2f5fa; border-color: #ece5d8; color: #938974; border-radius: .7rem; }
        .btn-brown { background: #2b4c80; color: #fff; border: none; border-radius: .7rem; font-weight: 700;
            box-shadow: 0 2px 8px rgba(43,76,128,.25); transition: all .15s; }
        .btn-brown:hover { color: #fff; filter: brightness(1.05); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(43,76,128,.3); }
        .form-foot { color: #a79f8f; font-size: .78rem; text-align: center; margin-top: 1.6rem; }

        /* موبايل: الصورة تختفي والفورم ياخد كل العرض فوق خلفية مصغّرة */
        @media (max-width: 860px) {
            .split-image { display: none; }
            .split-form { flex: 1 1 100%; box-shadow: none; }
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
                    <div class="alert alert-danger py-2 border-0" style="background:#f6ebea;color:#8f3f3b;border-radius:.7rem">
                        <i class="fa-solid fa-circle-exclamation ms-1"></i> {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus placeholder="admin@example.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" id="pw" class="form-control" required placeholder="••••••••">
                            <span class="input-group-text" role="button" onclick="const p=document.getElementById('pw');p.type=p.type==='password'?'text':'password';this.querySelector('i').classList.toggle('fa-eye');this.querySelector('i').classList.toggle('fa-eye-slash')"><i class="fa-solid fa-eye"></i></span>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">تذكّرني على هذا الجهاز</label>
                    </div>
                    <button type="submit" class="btn btn-brown w-100 py-2">
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
