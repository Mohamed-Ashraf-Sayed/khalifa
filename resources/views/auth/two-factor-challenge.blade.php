<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>المصادقة الثنائية — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body {
            background: linear-gradient(160deg, #1f3a63 0%, #14264a 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .login-card { width: 100%; max-width: 420px; border: none; border-radius: 18px; box-shadow: 0 18px 50px rgba(40,30,20,.32); }
        .login-head { background: #f3f6fb; border-radius: 18px 18px 0 0; padding: 2rem; text-align: center; }
        .login-head i { font-size: 2.5rem; color: #2b4c80; }
        .btn-brown { background: #2b4c80; color: #fff; }
        .btn-brown:hover { background: #1f3a63; color: #fff; }
        .code-input { letter-spacing: .5rem; text-align: center; font-size: 1.4rem; }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-head">
            <i class="fa-solid fa-shield-halved"></i>
            <h4 class="mt-2 mb-0">{{ config('app.name') }}</h4>
            <small class="text-muted">المصادقة الثنائية</small>
        </div>
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
            @endif
            <p class="text-muted small mb-3">أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة.</p>
            <form method="POST" action="{{ route('two_factor.verify') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">رمز التحقق</label>
                    <input type="text" name="code" dir="ltr" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                           class="form-control code-input" autocomplete="one-time-code" required autofocus>
                </div>
                <button type="submit" class="btn btn-brown w-100 py-2">
                    <i class="fa-solid fa-right-to-bracket ms-1"></i> تحقّق ودخول
                </button>
            </form>
            <form method="POST" action="{{ route('two_factor.cancel') }}" class="mt-3 text-center">
                @csrf
                <button type="submit" class="btn btn-link text-muted p-0">إلغاء والعودة لتسجيل الدخول</button>
            </form>
        </div>
    </div>
</body>
</html>
