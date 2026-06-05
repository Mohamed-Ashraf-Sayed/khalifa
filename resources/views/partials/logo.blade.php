{{-- شعار «خليفة حسين خليفة للمقاولات» — يستخدم logo.png لو موجود، وإلا SVG مدمج (يرث اللون عبر currentColor) --}}
@if (file_exists(public_path('images/logo.png')))
    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="app-logo">
@else
    <svg viewBox="0 0 300 160" class="app-logo" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true">
        <polygon points="12,66 22,58 22,150 12,150"/>
        <polygon points="30,50 42,40 42,150 30,150"/>
        <polygon points="50,30 64,18 64,150 50,150"/>
        <polygon points="72,72 82,64 82,150 72,150"/>
        <rect x="98" y="20" width="17" height="130"/>
        <polygon points="115,86 168,20 185,20 124,96"/>
        <polygon points="115,84 168,150 151,150 115,108"/>
        <rect x="205" y="20" width="16" height="130"/>
        <rect x="252" y="20" width="16" height="130"/>
        <rect x="205" y="77" width="63" height="16"/>
    </svg>
@endif
