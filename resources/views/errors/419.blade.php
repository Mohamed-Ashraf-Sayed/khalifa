<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 — انتهت الجلسة — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --beige: #eef2f9; --beige-dark: #e8e0cf; --brown: #2b4c80; --brown-dark: #1f3a63; }
        * { font-family: 'Cairo', sans-serif; }
        body { background: var(--beige); color: #3a342b; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 1.5rem; }
        .error-card { background: #fff; border-radius: 1rem; box-shadow: 0 8px 30px rgba(43,76,128,.15); padding: 3rem 2.5rem; max-width: 480px; width: 100%; text-align: center; }
        .error-code { font-size: 6rem; font-weight: 700; line-height: 1; color: var(--brown); margin-bottom: .5rem; }
        .error-icon { font-size: 2.5rem; color: var(--brown-dark); margin-bottom: 1rem; }
        .error-message { font-size: 1.4rem; font-weight: 600; color: #3a342b; margin-bottom: 1.75rem; }
        .btn-brown { background: var(--brown); color: #fff; border: none; padding: .6rem 1.6rem; border-radius: .6rem; font-weight: 600; transition: background .2s ease; }
        .btn-brown:hover { background: var(--brown-dark); color: #fff; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="error-code">419</div>
        <div class="error-message">انتهت الجلسة، حدّث الصفحة</div>
        <a href="{{ url('/dashboard') }}" class="btn btn-brown" style="background:#2b4c80;color:#fff">
            <i class="fa-solid fa-gauge-high ms-1"></i> العودة للوحة التحكم
        </a>
    </div>
</body>
</html>
