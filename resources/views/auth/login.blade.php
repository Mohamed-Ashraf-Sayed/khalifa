<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body {
            background: linear-gradient(135deg, #8b7355, #6f5b43);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .login-card { width: 100%; max-width: 420px; border: none; border-radius: 1rem; box-shadow: 0 15px 50px rgba(0,0,0,.3); }
        .login-head { background: #f5f1e8; border-radius: 1rem 1rem 0 0; padding: 2rem; text-align: center; }
        .login-head i { font-size: 2.5rem; color: #8b7355; }
        .btn-brown { background: #8b7355; color: #fff; }
        .btn-brown:hover { background: #6f5b43; color: #fff; }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-head">
            <i class="fa-solid fa-helmet-safety"></i>
            <h4 class="mt-2 mb-0">{{ config('app.name') }}</h4>
            <small class="text-muted">تسجيل الدخول</small>
        </div>
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label">تذكّرني</label>
                </div>
                <button type="submit" class="btn btn-brown w-100 py-2">
                    <i class="fa-solid fa-right-to-bracket ms-1"></i> دخول
                </button>
            </form>
        </div>
    </div>
</body>
</html>
