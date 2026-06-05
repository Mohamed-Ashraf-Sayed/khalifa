<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') — {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* محايدات دافئة هادئة */
            --bg: #f6f5f2; --bg-2: #efede8; --card: #fff;
            --line: #e9e6e0; --line-2: #f1efea;
            --ink: #2b2722; --ink-2: #5c564c; --muted: #8d877b;
            /* البني (هوية + الأزرار المضمّنة) */
            --brown: #8b7355; --brown-dark: #6f5b43; --brown-darker: #564633; --brown-light: #a3895f;
            --brown-50: #f3efe8; --brown-100: #e7dfd2;
            /* ألوان دلالية مكتومة (تُستخدم بهدوء) */
            --success: #4f8a6b; --success-bg: #eaf2ec;
            --danger: #b65f5b; --danger-bg: #f6ebea;
            --warning: #b8893f; --warning-bg: #f6efe2;
            --info: #5b7e96; --info-bg: #ebf0f3;
            --radius: 12px; --radius-sm: 9px;
            --shadow-sm: 0 1px 2px rgba(80,64,46,.05);
            --shadow: 0 2px 10px rgba(80,64,46,.06);
            --shadow-lg: 0 10px 28px rgba(80,64,46,.10);
            /* aliases للتوافق مع القواعد الحالية */
            --beige: var(--bg); --beige-dark: var(--bg-2);
            /* تجاوز ألوان Bootstrap لتهدئتها عالمياً */
            --bs-primary: #8b7355; --bs-primary-rgb: 139,115,85;
            --bs-success-rgb: 79,138,107; --bs-danger-rgb: 182,95,91;
            --bs-warning-rgb: 184,137,63; --bs-info-rgb: 91,126,150;
            --bs-secondary-rgb: 141,135,123;
            --bs-link-color: #6f5b43; --bs-link-hover-color: #564633;
        }
        * { font-family: 'Cairo', sans-serif; }
        body { background: var(--bg); color: var(--ink); -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; font-size: .92rem; letter-spacing: .003em; }
        ::selection { background: rgba(139,115,85,.22); }
        h1,h2,h3,h4,h5,h6 { font-weight: 700; color: var(--ink); letter-spacing: -.01em; }
        a { text-decoration: none; }

        /* ===== Sidebar ===== */
        .sidebar {
            background: linear-gradient(180deg, #6f5b43 0%, #5f4d3a 100%);
            height: 100vh; width: 264px; position: fixed; inset-inline-start: 0; top: 0; z-index: 1045;
            overflow-y: auto; overflow-x: hidden; transition: inset-inline-start .25s ease;
            box-shadow: 1px 0 0 rgba(0,0,0,.05);
        }
        .sidebar-backdrop { position: fixed; inset: 0; background: rgba(40,30,20,.45); backdrop-filter: blur(2px); z-index: 1040; display: none; }
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.22); border-radius: 3px; }
        .sidebar .brand { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: .6rem;
            background: rgba(0,0,0,.12); backdrop-filter: blur(6px); color: #fff; font-weight: 800; padding: 1.1rem 1.25rem; font-size: 1.12rem;
            border-bottom: 1px solid rgba(255,255,255,.12); letter-spacing: -.01em; }
        .sidebar .brand .brand-logo { background: rgba(255,255,255,.14); width: 40px; height: 40px; min-width: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 11px; padding: 5px; color: #fff; }
        .sidebar .brand .app-logo { width: 100%; height: 100%; object-fit: contain; }
        .sidebar .brand img.app-logo { filter: brightness(0) invert(1); }   /* لوجو أسود → أبيض على الخلفية البنية */
        .sidebar .nav-link { position: relative; color: rgba(255,255,255,.82); border-radius: .65rem; margin: .12rem .65rem; padding: .58rem .9rem; font-size: .9rem; font-weight: 500; transition: background .15s, color .15s, padding .15s; display: flex; align-items: center; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar .nav-link.active { background: rgba(255,255,255,.16); color: #fff; font-weight: 700; box-shadow: inset 0 0 0 1px rgba(255,255,255,.08); }
        .sidebar .nav-link.active::before { content: ''; position: absolute; inset-inline-start: -4px; top: 22%; bottom: 22%; width: 4px; border-radius: 4px; background: #fff; }
        .sidebar .nav-link i { width: 24px; text-align: center; opacity: .92; }
        .sidebar .nav-section { color: rgba(255,255,255,.42); font-size: .68rem; font-weight: 700; letter-spacing: .6px; padding: 1.1rem 1.5rem .35rem; text-transform: uppercase; }

        /* ===== Layout ===== */
        .main { margin-inline-start: 264px; min-height: 100vh; }
        .topbar { background: rgba(255,255,255,.92); backdrop-filter: blur(8px); border-bottom: 1px solid var(--line);
            position: sticky; top: 0; z-index: 10; min-height: 64px; }
        .topbar-titlewrap { line-height: 1.15; }
        .topbar-title { font-size: 1.18rem; font-weight: 800; margin: 0; letter-spacing: -.01em; }
        .topbar-sub { font-size: .72rem; color: var(--muted); font-weight: 600; margin-top: 1px; }
        .topbar-sub i { opacity: .7; margin-inline-start: 2px; }
        /* البحث */
        .topbar-search { flex: 1 1 280px; max-width: 460px; position: relative; align-items: center; }
        .topbar-search i { position: absolute; inset-inline-start: 14px; color: var(--muted); font-size: .85rem; pointer-events: none; }
        .topbar-search input { width: 100%; border: 1px solid var(--line); background: #faf7f0; border-radius: 50rem; padding: .5rem 2.4rem .5rem 1rem; font-size: .85rem; transition: all .15s; }
        .topbar-search input:focus { outline: none; background: #fff; border-color: var(--brown-light); box-shadow: 0 0 0 .2rem rgba(139,115,85,.13); }
        /* أزرار أيقونية */
        .icon-btn { width: 42px; height: 42px; border-radius: 12px; border: 1px solid var(--line); background: #fff; color: var(--brown-dark);
            display: inline-flex; align-items: center; justify-content: center; position: relative; transition: all .15s; font-size: 1rem; }
        .icon-btn:hover { background: var(--beige); border-color: var(--beige-dark); color: var(--brown-darker); }
        .notif-dot { position: absolute; top: -5px; inset-inline-start: -5px; min-width: 19px; height: 19px; padding: 0 4px; border-radius: 50rem;
            background: var(--danger); color: #fff; font-size: .68rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; border: 2px solid #fff; }
        /* كارت المستخدم */
        .user-chip { display: flex; align-items: center; gap: .5rem; background: #fff; border: 1px solid var(--line); border-radius: 50rem;
            padding: .25rem .85rem .25rem .3rem; cursor: pointer; transition: all .15s; }
        .user-chip:hover { background: var(--beige); border-color: var(--beige-dark); }
        .user-chip .avatar { width: 34px; height: 34px; min-width: 34px; border-radius: 50%; overflow: hidden;
            background: linear-gradient(145deg, #93795a, #6f5b43); color: #fff; font-weight: 700; font-size: .95rem;
            display: inline-flex; align-items: center; justify-content: center; }
        .user-chip .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-chip .uinfo { flex-direction: column; align-items: flex-start; line-height: 1.1; }
        .user-chip .uname { font-weight: 700; font-size: .85rem; color: var(--ink); }
        .user-chip .urole { font-size: .68rem; color: var(--muted); font-weight: 600; }
        .user-chip .chev { font-size: .7rem; color: var(--muted); margin-inline-start: .15rem; }
        main.px-4 { animation: fadein .25s ease; }
        @keyframes fadein { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

        /* ===== Cards ===== */
        .card { border: 1px solid var(--line); box-shadow: var(--shadow-sm); border-radius: var(--radius); background: var(--card); }
        .card .card-body { padding: 1.25rem 1.4rem; }
        .card h6 { font-weight: 700; font-size: .95rem; letter-spacing: -.005em; }

        /* ===== مكوّنات صفحات العرض (entity show) ===== */
        .entity-avatar { width: 60px; height: 60px; min-width: 60px; border-radius: 16px; background: linear-gradient(145deg,#8b7355,#6f5b43); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 12px rgba(139,115,85,.18); }
        .stat-box { border: 1px solid var(--line); border-radius: 13px; padding: .75rem .9rem; height: 100%; background: #fff; }
        .stat-box .sl { font-size: .72rem; color: var(--muted); font-weight: 600; }
        .stat-box .sv { font-size: 1.2rem; font-weight: 800; letter-spacing: -.02em; line-height: 1.25; }
        .stat-box.accent { background: var(--brown-50); border-color: var(--brown-100); }
        .info-list { display: grid; grid-template-columns: 1fr 1fr; gap: .1rem .9rem; }
        @media (max-width: 600px) { .info-list { grid-template-columns: 1fr; } }
        .info-list .il { display: flex; justify-content: space-between; gap: 1rem; padding: .6rem .25rem; border-bottom: 1px dashed var(--line); }
        .info-list .il .k { color: var(--muted); font-size: .85rem; }
        .info-list .il .v { font-weight: 600; text-align: end; }

        /* ===== Buttons ===== */
        .btn { border-radius: .6rem; font-weight: 600; font-size: .9rem; transition: all .15s ease; }
        .btn:active { transform: translateY(1px); }
        .btn-light { background: #fff; border-color: var(--line); color: var(--ink); }
        .btn-light:hover { background: var(--beige); border-color: var(--beige-dark); }
        .btn-sm { border-radius: .5rem; }
        /* الأزرار البنية (style مضمّن) — تحسين شامل (مقصور على الأزرار والروابط) */
        .btn[style*="8b7355"], button[style*="8b7355"], a.btn[style*="8b7355"] { border: none !important; border-radius: .55rem; box-shadow: 0 1px 3px rgba(139,115,85,.22); transition: all .15s ease; font-weight: 600; }
        .btn[style*="8b7355"]:hover, button[style*="8b7355"]:hover, a.btn[style*="8b7355"]:hover { filter: brightness(1.05); box-shadow: 0 3px 10px rgba(139,115,85,.26); transform: translateY(-1px); }
        .btn-success { box-shadow: 0 1px 3px rgba(79,138,107,.2); }
        .btn-outline-primary, .btn-outline-secondary, .btn-outline-danger { border-radius: .5rem; }

        /* ===== Tables ===== */
        .table { --bs-table-bg: transparent; color: var(--ink); margin-bottom: 0; }
        .table > thead { background: var(--bg); }
        .table > thead th, .table-light > tr > th, thead.table-light th { background: var(--bg); color: var(--muted); font-weight: 700; font-size: .78rem; text-transform: none; letter-spacing: .2px; border-bottom: 1px solid var(--line); padding: .78rem .85rem; white-space: nowrap; }
        .table > tbody > tr > td { padding: .78rem .85rem; vertical-align: middle; border-bottom: 1px solid var(--line-2); }
        .table-hover > tbody > tr { transition: background .12s; }
        .table-hover > tbody > tr:hover { background: var(--bg); }
        .table > tbody > tr:last-child > td { border-bottom: none; }

        /* ===== Badges / status (مكتومة وهادئة) ===== */
        .badge { font-weight: 600; border-radius: 50rem; padding: .34em .7em; letter-spacing: 0; }
        .badge.rounded-pill { border-radius: 50rem; }
        .badge.text-bg-success, .badge.bg-success   { background: var(--success-bg) !important; color: #356a4f !important; }
        .badge.text-bg-danger,  .badge.bg-danger    { background: var(--danger-bg)  !important; color: #8f3f3b !important; }
        .badge.text-bg-warning, .badge.bg-warning   { background: var(--warning-bg) !important; color: #8a6322 !important; }
        .badge.text-bg-info,    .badge.bg-info      { background: var(--info-bg)    !important; color: #3f5d70 !important; }
        .badge.text-bg-secondary, .badge.bg-secondary { background: var(--bg-2)     !important; color: var(--ink-2) !important; }
        .badge.text-bg-primary, .badge.bg-primary   { background: var(--brown-50)   !important; color: var(--brown-darker) !important; }
        .badge.text-bg-light,   .badge.bg-light     { background: var(--bg-2)       !important; color: var(--ink-2) !important; }

        /* ===== تهدئة ألوان الأيقونات/الأرقام عالمياً (334 استخدام) ===== */
        .text-success { color: var(--success) !important; }
        .text-danger  { color: var(--danger)  !important; }
        .text-warning { color: var(--warning) !important; }
        .text-info    { color: var(--info)    !important; }
        .text-primary { color: var(--brown)   !important; }
        .text-secondary { color: var(--muted) !important; }

        /* ===== Forms ===== */
        .form-label { font-weight: 600; font-size: .85rem; color: #5b5443; margin-bottom: .3rem; }
        .form-control, .form-select { border-radius: .6rem; border-color: var(--line); background: #fdfcfa; font-size: .9rem; padding: .5rem .8rem; transition: border-color .15s, box-shadow .15s; }
        .form-control:focus, .form-select:focus { border-color: var(--brown-light); box-shadow: 0 0 0 .2rem rgba(139,115,85,.15); background: #fff; }
        .input-group-text { background: #faf7f0; border-color: var(--line); border-radius: .6rem; color: var(--muted); }

        /* ===== Pagination ===== */
        .pagination { --bs-pagination-color: var(--brown-dark); --bs-pagination-hover-color: var(--brown-darker);
            --bs-pagination-active-bg: var(--brown); --bs-pagination-active-border-color: var(--brown);
            --bs-pagination-border-radius: .55rem; --bs-pagination-hover-bg: var(--beige); gap: .25rem; }
        .page-link { border-radius: .55rem !important; border-color: var(--line); margin: 0 1px; }

        /* ===== Alerts ===== */
        .alert { border: none; border-radius: .75rem; box-shadow: var(--shadow-sm); font-weight: 500; }
        .alert-success { background: var(--success-bg); color: #356a4f; }
        .alert-danger { background: var(--danger-bg); color: #8f3f3b; }
        .alert-warning { background: var(--warning-bg); color: #8a6322; }
        .alert-info { background: var(--info-bg); color: #3f5d70; }

        /* ===== Dropdowns ===== */
        .dropdown-menu { border: 1px solid var(--line); border-radius: .8rem; box-shadow: var(--shadow-lg); padding: .4rem; }
        .dropdown-item { border-radius: .5rem; padding: .5rem .7rem; font-size: .9rem; }
        .dropdown-item:hover { background: var(--beige); }
        .dropdown-header { font-weight: 700; color: var(--muted); }

        /* ===== Misc ===== */
        .text-muted { color: var(--muted) !important; }
        body::-webkit-scrollbar { width: 11px; } body::-webkit-scrollbar-thumb { background: #d9cfbd; border-radius: 6px; border: 3px solid var(--beige); }

        @media (max-width: 768px) {
            .sidebar { inset-inline-start: -274px; }
            .sidebar.show { inset-inline-start: 0; }
            .sidebar.show ~ .sidebar-backdrop { display: block; }
            .main { margin-inline-start: 0; }
        }
        @media print {
            .sidebar, .topbar, .sidebar-backdrop, .no-print { display: none !important; }
            .main { margin-inline-start: 0 !important; }
            body { background: #fff; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @php($u = auth()->user())
    <aside class="sidebar text-white">
        <div class="brand"><span class="brand-logo">@include('partials.logo')</span>{{ config('app.name') }}</div>
        <nav class="nav flex-column py-2">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="fa-solid fa-gauge-high"></i> لوحة التحكم
            </a>
            @canany(['projects.view','clients.view','contracts.view'])
                <div class="nav-section">العمليات</div>
                @can('projects.view')   <a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}"><i class="fa-solid fa-diagram-project"></i> المشاريع</a> @endcan
                @can('clients.view')    <a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}"><i class="fa-solid fa-users"></i> العملاء</a> @endcan
                @can('contracts.view')  <a class="nav-link {{ request()->routeIs('contracts.*') ? 'active' : '' }}" href="{{ route('contracts.index') }}"><i class="fa-solid fa-file-contract"></i> عقود المشاريع</a> @endcan
                @can('projects.view')   <a class="nav-link {{ request()->routeIs('project_costs.*') ? 'active' : '' }}" href="{{ route('project_costs.index') }}"><i class="fa-solid fa-coins"></i> تكاليف المشاريع</a> @endcan
                @can('projects.view')   <a class="nav-link {{ request()->routeIs('project_files.*') ? 'active' : '' }}" href="{{ route('project_files.index') }}"><i class="fa-solid fa-folder-open"></i> ملفات المشاريع</a> @endcan
                @can('projects.view')   <a class="nav-link {{ request()->routeIs('daily_site_reports.*') ? 'active' : '' }}" href="{{ route('daily_site_reports.index') }}"><i class="fa-solid fa-clipboard-list"></i> يومية الموقع</a> @endcan
                @can('projects.view')   <a class="nav-link {{ request()->routeIs('labor_attendances.*') ? 'active' : '' }}" href="{{ route('labor_attendances.index') }}"><i class="fa-solid fa-user-clock"></i> حضور العمالة</a> @endcan
                @can('contracts.view')  <a class="nav-link {{ request()->routeIs('change_orders.*') ? 'active' : '' }}" href="{{ route('change_orders.index') }}"><i class="fa-solid fa-file-pen"></i> أوامر التغيير</a> @endcan
                @can('projects.view')   <a class="nav-link {{ request()->routeIs('snags.*') ? 'active' : '' }}" href="{{ route('snags.index') }}"><i class="fa-solid fa-clipboard-check"></i> قائمة العيوب</a> @endcan
                @can('projects.view')   <a class="nav-link {{ request()->routeIs('rfis.*') ? 'active' : '' }}" href="{{ route('rfis.index') }}"><i class="fa-solid fa-circle-question"></i> طلبات الاستفسار (RFI)</a> @endcan
            @endcanany

            @canany(['tenders.view','quotations.view'])
                <div class="nav-section">المناقصات والعروض</div>
                @can('tenders.view')    <a class="nav-link {{ request()->routeIs('tenders.*') ? 'active' : '' }}" href="{{ route('tenders.index') }}"><i class="fa-solid fa-gavel"></i> المناقصات</a> @endcan
                @can('quotations.view') <a class="nav-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}" href="{{ route('quotations.index') }}"><i class="fa-solid fa-file-invoice-dollar"></i> عروض الأسعار</a> @endcan
            @endcanany

            @can('guarantees.view')
                <div class="nav-section">الضمانات والتأمينات</div>
                <a class="nav-link {{ request()->routeIs('guarantees.*') ? 'active' : '' }}" href="{{ route('guarantees.index') }}"><i class="fa-solid fa-shield-halved"></i> خطابات الضمان</a>
                <a class="nav-link {{ request()->routeIs('insurance.*') ? 'active' : '' }}" href="{{ route('insurance.index') }}"><i class="fa-solid fa-file-shield"></i> وثائق التأمين</a>
            @endcan

            @canany(['contractors.view','suppliers.view','purchase_orders.view'])
                <div class="nav-section">المقاولون والموردون</div>
                @can('contractors.view')<a class="nav-link {{ request()->routeIs('contractors.*') ? 'active' : '' }}" href="{{ route('contractors.index') }}"><i class="fa-solid fa-hard-hat"></i> المقاولون</a> @endcan
                @can('contractors.view')<a class="nav-link {{ request()->routeIs('contractor_extracts.*') ? 'active' : '' }}" href="{{ route('contractor_extracts.index') }}"><i class="fa-solid fa-file-invoice"></i> مستخلصات المقاولين</a> @endcan
                @can('contractors.view')<a class="nav-link {{ request()->routeIs('contractor_payments.*') ? 'active' : '' }}" href="{{ route('contractor_payments.index') }}"><i class="fa-solid fa-hand-holding-dollar"></i> دفعات المقاولين</a> @endcan
                @can('suppliers.view')  <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><i class="fa-solid fa-truck"></i> الموردون</a> @endcan
                @can('purchase_orders.view')<a class="nav-link {{ request()->routeIs('purchase_orders.*') ? 'active' : '' }}" href="{{ route('purchase_orders.index') }}"><i class="fa-solid fa-cart-shopping"></i> أوامر الشراء</a> @endcan
                @can('suppliers.view')  <a class="nav-link {{ request()->routeIs('supplier_transactions.*') ? 'active' : '' }}" href="{{ route('supplier_transactions.index') }}"><i class="fa-solid fa-bag-shopping"></i> مشتريات الموردين</a> @endcan
                @can('suppliers.view')  <a class="nav-link {{ request()->routeIs('supplier_payments.*') ? 'active' : '' }}" href="{{ route('supplier_payments.index') }}"><i class="fa-solid fa-money-check-dollar"></i> مدفوعات الموردين</a> @endcan
            @endcanany

            @canany(['expenses.view','revenues.view','invoices.view','bank_accounts.view','taxes.view'])
                <div class="nav-section">المالية</div>
                @can('expenses.view')   <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><i class="fa-solid fa-money-bill-wave"></i> المصروفات</a> @endcan
                @can('expenses.view')   <a class="nav-link {{ request()->routeIs('expense_categories.*') ? 'active' : '' }}" href="{{ route('expense_categories.index') }}"><i class="fa-solid fa-tags"></i> فئات المصروفات</a> @endcan
                @can('revenues.view')   <a class="nav-link {{ request()->routeIs('revenues.*') ? 'active' : '' }}" href="{{ route('revenues.index') }}"><i class="fa-solid fa-sack-dollar"></i> الإيرادات</a> @endcan
                @can('invoices.view')   <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><i class="fa-solid fa-file-lines"></i> الفواتير</a> @endcan
                @can('taxes.view')      <a class="nav-link {{ request()->routeIs('taxes.*') ? 'active' : '' }}" href="{{ route('taxes.index') }}"><i class="fa-solid fa-file-invoice-dollar"></i> الضرائب</a> @endcan
                @can('bank_accounts.view')<a class="nav-link {{ request()->routeIs('bank_accounts.*') ? 'active' : '' }}" href="{{ route('bank_accounts.index') }}"><i class="fa-solid fa-building-columns"></i> الحسابات البنكية</a> @endcan
                @can('bank_accounts.view')<a class="nav-link {{ request()->routeIs('bank_transfers.*') ? 'active' : '' }}" href="{{ route('bank_transfers.index') }}"><i class="fa-solid fa-right-left"></i> التحويلات البنكية</a> @endcan
                @can('bank_accounts.view')<a class="nav-link {{ request()->routeIs('cheques.*') ? 'active' : '' }}" href="{{ route('cheques.index') }}"><i class="fa-solid fa-money-check"></i> سجل الشيكات</a> @endcan
                @can('bank_accounts.view')<a class="nav-link {{ request()->routeIs('payment_methods.*') ? 'active' : '' }}" href="{{ route('payment_methods.index') }}"><i class="fa-solid fa-credit-card"></i> طرق الدفع</a> @endcan
            @endcanany

            @canany(['materials.view','assets.view'])
                <div class="nav-section">المخزون والأصول</div>
                @can('materials.view')  <a class="nav-link {{ request()->routeIs('materials.*') ? 'active' : '' }}" href="{{ route('materials.index') }}"><i class="fa-solid fa-boxes-stacked"></i> المواد</a> @endcan
                @can('materials.view')  <a class="nav-link {{ request()->routeIs('inventory_movements.*') ? 'active' : '' }}" href="{{ route('inventory_movements.index') }}"><i class="fa-solid fa-dolly"></i> حركات المخزون</a> @endcan
                @can('materials.view')  <a class="nav-link {{ request()->routeIs('material_requisitions.*') ? 'active' : '' }}" href="{{ route('material_requisitions.index') }}"><i class="fa-solid fa-clipboard-check"></i> أذون صرف المواد</a> @endcan
                @can('assets.view')     <a class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}" href="{{ route('assets.index') }}"><i class="fa-solid fa-warehouse"></i> الأصول الثابتة</a> @endcan
                @can('assets.view')     <a class="nav-link {{ request()->routeIs('equipment_logs.*') ? 'active' : '' }}" href="{{ route('equipment_logs.index') }}"><i class="fa-solid fa-screwdriver-wrench"></i> سجل المعدات</a> @endcan
            @endcanany

            @canany(['employees.view','partners.view'])
                <div class="nav-section">الموارد البشرية والشركاء</div>
                @can('employees.view')  <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}"><i class="fa-solid fa-id-badge"></i> الموظفون</a> @endcan
                @can('employees.view')  <a class="nav-link {{ request()->routeIs('employee_transactions.*') ? 'active' : '' }}" href="{{ route('employee_transactions.index') }}"><i class="fa-solid fa-wallet"></i> معاملات الموظفين</a> @endcan
                @can('partners.view')   <a class="nav-link {{ request()->routeIs('partners.*') ? 'active' : '' }}" href="{{ route('partners.index') }}"><i class="fa-solid fa-handshake"></i> الشركاء</a> @endcan
                @can('partners.view')   <a class="nav-link {{ request()->routeIs('partner_transactions.*') ? 'active' : '' }}" href="{{ route('partner_transactions.index') }}"><i class="fa-solid fa-coins"></i> حركات الشركاء</a> @endcan
                @can('partners.view')<a class="nav-link {{ request()->routeIs('partner_deposits.*') ? 'active' : '' }}" href="{{ route('partner_deposits.index') }}"><i class="fa-solid fa-piggy-bank"></i> إيداعات الشركاء</a> @endcan
            @endcanany

            @canany(['reports.view','users.view','settings.view'])
                <div class="nav-section">التقارير والنظام</div>
                @can('reports.view')    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="fa-solid fa-chart-line"></i> التقارير</a> @endcan
                @can('reports.view')    <a class="nav-link {{ request()->routeIs('general_ledger.*') ? 'active' : '' }}" href="{{ route('general_ledger.index') }}"><i class="fa-solid fa-book"></i> دفتر الأستاذ</a> @endcan
                @can('reports.view')    <a class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}" href="{{ route('analytics.project_profitability') }}"><i class="fa-solid fa-chart-pie"></i> التحليلات</a> @endcan
                @can('reports.view')    <a class="nav-link {{ request()->routeIs('cost_centers.*') ? 'active' : '' }}" href="{{ route('cost_centers.index') }}"><i class="fa-solid fa-sitemap"></i> مراكز التكلفة</a> @endcan
                @can('users.view')      <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="fa-solid fa-user-gear"></i> المستخدمون</a> @endcan
                @can('users.view')      <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><i class="fa-solid fa-user-shield"></i> الأدوار والصلاحيات</a> @endcan
                @can('users.view')      <a class="nav-link {{ request()->routeIs('trash.*') ? 'active' : '' }}" href="{{ route('trash.index') }}"><i class="fa-solid fa-trash-can-arrow-up"></i> سلة المحذوفات</a> @endcan
                @can('users.view')      <a class="nav-link {{ request()->routeIs('activity_logs.*') ? 'active' : '' }}" href="{{ route('activity_logs.index') }}"><i class="fa-solid fa-clock-rotate-left"></i> سجل النشاطات</a> @endcan
                @can('users.view')      <a class="nav-link {{ request()->routeIs('login_logs.*') ? 'active' : '' }}" href="{{ route('login_logs.index') }}"><i class="fa-solid fa-right-to-bracket"></i> سجل الدخول</a> @endcan
                @can('settings.view')   <a class="nav-link {{ request()->routeIs('data_port.*') ? 'active' : '' }}" href="{{ route('data_port.index') }}"><i class="fa-solid fa-file-import"></i> استيراد/تصدير</a> @endcan
                @can('settings.view')   <a class="nav-link {{ request()->routeIs('backups.*') ? 'active' : '' }}" href="{{ route('backups.index') }}"><i class="fa-solid fa-database"></i> النسخ الاحتياطي</a> @endcan
                @can('settings.view')   <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}"><i class="fa-solid fa-gear"></i> الإعدادات</a> @endcan
            @endcanany
        </nav>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        <header class="topbar d-flex align-items-center gap-3 px-4 py-2 mb-4">
            <button class="icon-btn d-md-none" id="sidebarToggle" type="button" aria-label="القائمة"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-titlewrap">
                <h5 class="topbar-title">@yield('title', 'لوحة التحكم')</h5>
                <div class="topbar-sub"><i class="fa-regular fa-calendar-days"></i> {{ \Illuminate\Support\Carbon::now()->translatedFormat('l، j F Y') }}</div>
            </div>

            <form method="GET" action="{{ route('search') }}" class="topbar-search d-none d-lg-flex" role="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="بحث شامل عن مشروع، عميل، فاتورة، مورد...">
            </form>

            <div class="topbar-actions d-flex align-items-center gap-2 ms-auto">
                @inject('alerts', 'App\Services\AlertService')
                @php($alertItems = $alerts->items())
                <div class="dropdown">
                    <button class="icon-btn" data-bs-toggle="dropdown" aria-label="التنبيهات">
                        <i class="fa-solid fa-bell"></i>
                        @if (count($alertItems))
                            <span class="notif-dot">{{ array_sum(array_column($alertItems, 'count')) }}</span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width:320px">
                        <li class="px-2 pb-1 d-flex justify-content-between align-items-center">
                            <h6 class="dropdown-header px-1 mb-0">التنبيهات التشغيلية</h6>
                            <a href="{{ route('notifications.index') }}" class="small text-decoration-none" style="color:#8b7355">عرض الكل</a>
                        </li>
                        @forelse ($alertItems as $a)
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ $a['url'] }}">
                                    <span><i class="fa-solid {{ $a['icon'] }} text-{{ $a['color'] }} ms-2"></i> {{ $a['label'] }}</span>
                                    <span class="badge text-bg-{{ $a['color'] }} rounded-pill">{{ $a['count'] }}</span>
                                </a>
                            </li>
                        @empty
                            <li><span class="dropdown-item text-muted"><i class="fa-solid fa-circle-check text-success ms-1"></i> لا توجد تنبيهات حالياً</span></li>
                        @endforelse
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="user-chip" data-bs-toggle="dropdown">
                        <span class="avatar">
                            @if ($u->avatar)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($u->avatar) }}" alt="">
                            @else
                                {{ mb_substr($u->name, 0, 1) }}
                            @endif
                        </span>
                        <span class="uinfo d-none d-md-flex">
                            <span class="uname">{{ $u->name }}</span>
                            <span class="urole">{{ \App\Models\User::ROLE_LABELS[$u->getRoleNames()->first()] ?? $u->getRoleNames()->first() }}</span>
                        </span>
                        <i class="fa-solid fa-chevron-down chev"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width:220px">
                        <li class="px-3 py-2 d-md-none border-bottom mb-1">
                            <div class="fw-bold">{{ $u->name }}</div>
                            <div class="small text-muted">{{ $u->getRoleNames()->first() }}</div>
                        </li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fa-solid fa-user ms-2 text-muted"></i> الملف الشخصي</a></li>
                        @can('settings.view')<li><a class="dropdown-item" href="{{ route('settings.edit') }}"><i class="fa-solid fa-gear ms-2 text-muted"></i> الإعدادات</a></li>@endcan
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit"><i class="fa-solid fa-right-from-bracket ms-2"></i> تسجيل الخروج</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="px-4 pb-5">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show"><i class="fa-solid fa-circle-check ms-1"></i> {{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show"><i class="fa-solid fa-circle-exclamation ms-1"></i> {{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>فيه أخطاء في البيانات:</strong>
                    <ul class="mb-0 mt-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const sb = document.querySelector('.sidebar');
            const bd = document.getElementById('sidebarBackdrop');
            const tg = document.getElementById('sidebarToggle');
            const close = () => sb && sb.classList.remove('show');
            tg && tg.addEventListener('click', () => sb.classList.toggle('show'));
            bd && bd.addEventListener('click', close);
            document.querySelectorAll('.sidebar .nav-link').forEach(l => l.addEventListener('click', () => { if (window.innerWidth <= 768) close(); }));
            // إخفاء تنبيهات النجاح تلقائياً بعد 4 ثوانٍ
            document.querySelectorAll('.alert-success').forEach(a => setTimeout(() => { try { bootstrap.Alert.getOrCreateInstance(a).close(); } catch (e) {} }, 4000));
        })();
    </script>
    @stack('scripts')
</body>
</html>
