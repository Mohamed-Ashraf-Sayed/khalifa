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
        * { font-family: 'Cairo', sans-serif; }
        body {
            background: radial-gradient(1200px 600px at 80% -10%, #a3895f 0%, transparent 55%),
                        linear-gradient(150deg, #8b7355 0%, #6f5b43 50%, #564633 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem;
            position: relative; overflow: hidden;
        }
        body::before { content: ''; position: absolute; width: 480px; height: 480px; border-radius: 50%;
            background: rgba(255,255,255,.05); top: -160px; inset-inline-start: -120px; }
        body::after { content: ''; position: absolute; width: 360px; height: 360px; border-radius: 50%;
            background: rgba(255,255,255,.04); bottom: -140px; inset-inline-end: -100px; }
        .login-card { width: 100%; max-width: 410px; border: none; border-radius: 20px; position: relative; z-index: 2;
            box-shadow: 0 24px 60px rgba(40,30,20,.45); overflow: hidden; }
        .login-head { background: linear-gradient(#fbf8f2, #f4f1ea); padding: 2.4rem 2rem 1.4rem; text-align: center; }
        .brand-badge { width: 84px; height: 84px; border-radius: 20px; margin: 0 auto .9rem;
            background: #fff; color: #2f2a22; display: flex; align-items: center; justify-content: center; padding: 12px;
            box-shadow: 0 10px 24px rgba(139,115,85,.28); border: 1px solid #ece5d8; }
        .brand-badge .app-logo { width: 100%; height: 100%; object-fit: contain; }
        .login-head h4 { font-weight: 800; color: #2f2a22; margin: 0; letter-spacing: -.01em; }
        .login-head small { color: #938974; }
        .form-label { font-weight: 600; font-size: .85rem; color: #5b5443; }
        .form-control { border-radius: .7rem; border-color: #ece5d8; padding: .62rem .9rem; background: #fdfcfa; }
        .form-control:focus { border-color: #a3895f; box-shadow: 0 0 0 .2rem rgba(139,115,85,.15); background: #fff; }
        .input-group-text { background: #faf7f0; border-color: #ece5d8; color: #938974; border-radius: .7rem; }
        .btn-brown { background: linear-gradient(120deg, #93795a, #6f5b43); color: #fff; border: none; border-radius: .7rem; font-weight: 700;
            box-shadow: 0 6px 18px rgba(139,115,85,.35); transition: all .15s; }
        .btn-brown:hover { color: #fff; filter: brightness(1.07); transform: translateY(-1px); box-shadow: 0 9px 24px rgba(139,115,85,.45); }
        .login-foot { color: rgba(255,255,255,.65); font-size: .8rem; text-align: center; margin-top: 1.1rem; position: relative; z-index: 2; }
    </style>
</head>
<body>
    <div>
        <div class="card login-card">
            <div class="login-head">
                <div class="brand-badge">@include('partials.logo')</div>
                <h4>{{ config('app.name') }}</h4>
                <small>سجّل دخولك للمتابعة</small>
            </div>
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger py-2 border-0" style="background:#fbeaea;color:#a12a2a;border-radius:.7rem">
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
            </div>
        </div>
        <div class="login-foot">© {{ date('Y') }} {{ config('app.name') }} — جميع الحقوق محفوظة</div>
    </div>
</body>
</html>
