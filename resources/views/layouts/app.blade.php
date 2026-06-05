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
            --beige: #f4f1ea; --beige-dark: #e8e0cf;
            --brown: #8b7355; --brown-dark: #6f5b43; --brown-darker: #564633; --brown-light: #a3895f;
            --ink: #2f2a22; --muted: #938974; --line: #ece5d8; --card: #fff;
            --radius: 14px;
            --shadow-sm: 0 1px 2px rgba(87,70,51,.06), 0 1px 3px rgba(87,70,51,.05);
            --shadow: 0 4px 16px rgba(87,70,51,.07), 0 1px 4px rgba(87,70,51,.05);
            --shadow-lg: 0 12px 32px rgba(87,70,51,.12);
            --bs-primary: #8b7355;
        }
        * { font-family: 'Cairo', sans-serif; }
        body { background: var(--beige); color: var(--ink); -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; font-size: .95rem; }
        ::selection { background: rgba(139,115,85,.22); }
        h1,h2,h3,h4,h5,h6 { font-weight: 700; color: var(--ink); letter-spacing: -.01em; }
        a { text-decoration: none; }

        /* ===== Sidebar ===== */
        .sidebar {
            background: linear-gradient(168deg, #93795a 0%, var(--brown-dark) 55%, var(--brown-darker) 100%);
            height: 100vh; width: 264px; position: fixed; inset-inline-start: 0; top: 0; z-index: 1045;
            overflow-y: auto; overflow-x: hidden; transition: inset-inline-start .25s ease;
            box-shadow: 2px 0 18px rgba(87,70,51,.18);
        }
        .sidebar-backdrop { position: fixed; inset: 0; background: rgba(40,30,20,.45); backdrop-filter: blur(2px); z-index: 1040; display: none; }
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.22); border-radius: 3px; }
        .sidebar .brand { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: .6rem;
            background: rgba(40,30,20,.28); backdrop-filter: blur(6px); color: #fff; font-weight: 800; padding: 1.1rem 1.25rem; font-size: 1.12rem;
            border-bottom: 1px solid rgba(255,255,255,.12); letter-spacing: -.01em; }
        .sidebar .brand i { background: rgba(255,255,255,.15); width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 1rem; }
        .sidebar .nav-link { position: relative; color: rgba(255,255,255,.82); border-radius: .65rem; margin: .12rem .65rem; padding: .58rem .9rem; font-size: .9rem; font-weight: 500; transition: background .15s, color .15s, padding .15s; display: flex; align-items: center; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar .nav-link.active { background: rgba(255,255,255,.16); color: #fff; font-weight: 700; box-shadow: inset 0 0 0 1px rgba(255,255,255,.08); }
        .sidebar .nav-link.active::before { content: ''; position: absolute; inset-inline-start: -4px; top: 22%; bottom: 22%; width: 4px; border-radius: 4px; background: #fff; }
        .sidebar .nav-link i { width: 24px; text-align: center; opacity: .92; }
        .sidebar .nav-section { color: rgba(255,255,255,.42); font-size: .68rem; font-weight: 700; letter-spacing: .6px; padding: 1.1rem 1.5rem .35rem; text-transform: uppercase; }

        /* ===== Layout ===== */
        .main { margin-inline-start: 264px; min-height: 100vh; }
        .topbar { background: rgba(255,255,255,.85); backdrop-filter: saturate(1.4) blur(8px); border-bottom: 1px solid var(--line);
            position: sticky; top: 0; z-index: 10; }
        .topbar h5 { font-weight: 700; }
        main.px-4 { animation: fadein .25s ease; }
        @keyframes fadein { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

        /* ===== Cards ===== */
        .card { border: 1px solid var(--line); box-shadow: var(--shadow-sm); border-radius: var(--radius); background: var(--card); }
        .card .card-body { padding: 1.15rem 1.25rem; }
        .card h6 { font-weight: 700; }

        /* ===== Buttons ===== */
        .btn { border-radius: .6rem; font-weight: 600; font-size: .9rem; transition: all .15s ease; }
        .btn:active { transform: translateY(1px); }
        .btn-light { background: #fff; border-color: var(--line); color: var(--ink); }
        .btn-light:hover { background: var(--beige); border-color: var(--beige-dark); }
        .btn-sm { border-radius: .5rem; }
        /* الأزرار البنية (style مضمّن) — تحسين شامل (مقصور على الأزرار والروابط) */
        .btn[style*="8b7355"], button[style*="8b7355"], a.btn[style*="8b7355"] { border: none !important; border-radius: .6rem; box-shadow: 0 2px 8px rgba(139,115,85,.25); transition: all .15s ease; }
        .btn[style*="8b7355"]:hover, button[style*="8b7355"]:hover, a.btn[style*="8b7355"]:hover { filter: brightness(1.07); box-shadow: 0 5px 16px rgba(139,115,85,.34); transform: translateY(-1px); }
        .btn-success { box-shadow: 0 2px 8px rgba(31,157,107,.25); }
        .btn-outline-primary, .btn-outline-secondary, .btn-outline-danger { border-radius: .5rem; }

        /* ===== Tables ===== */
        .table { --bs-table-bg: transparent; color: var(--ink); margin-bottom: 0; }
        .table > thead { background: linear-gradient(var(--beige), #fff); }
        .table > thead th, .table-light > tr > th, thead.table-light th { background: #faf7f0; color: var(--muted); font-weight: 700; font-size: .76rem; text-transform: uppercase; letter-spacing: .3px; border-bottom: 1px solid var(--line); padding: .7rem .75rem; white-space: nowrap; }
        .table > tbody > tr > td { padding: .72rem .75rem; vertical-align: middle; border-bottom: 1px solid var(--line); }
        .table-hover > tbody > tr { transition: background .12s; }
        .table-hover > tbody > tr:hover { background: #faf7f0; }
        .table > tbody > tr:last-child > td { border-bottom: none; }

        /* ===== Badges / status ===== */
        .badge { font-weight: 600; border-radius: .5rem; padding: .38em .65em; letter-spacing: 0; }
        .badge.rounded-pill { border-radius: 50rem; }

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
        .alert-success { background: #e7f6ee; color: #166c47; }
        .alert-danger { background: #fbeaea; color: #a12a2a; }

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
        <div class="brand"><i class="fa-solid fa-helmet-safety ms-2"></i>{{ config('app.name') }}</div>
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
            @endcanany

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
                @can('assets.view')     <a class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}" href="{{ route('assets.index') }}"><i class="fa-solid fa-warehouse"></i> الأصول الثابتة</a> @endcan
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
        <header class="topbar d-flex align-items-center justify-content-between px-4 py-2 mb-4">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light d-md-none" id="sidebarToggle" type="button" aria-label="القائمة"><i class="fa-solid fa-bars"></i></button>
                <h5 class="m-0">@yield('title', 'لوحة التحكم')</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
            <form method="GET" action="{{ route('search') }}" class="d-none d-md-flex align-items-center" role="search">
                <div class="input-group input-group-sm" style="width:240px">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0" placeholder="بحث شامل...">
                </div>
            </form>
            @inject('alerts', 'App\Services\AlertService')
            @php($alertItems = $alerts->items())
            <div class="dropdown">
                <button class="btn btn-light position-relative" data-bs-toggle="dropdown" aria-label="التنبيهات">
                    <i class="fa-solid fa-bell"></i>
                    @if (count($alertItems))
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">{{ array_sum(array_column($alertItems, 'count')) }}</span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:300px">
                    <li><h6 class="dropdown-header">التنبيهات</h6></li>
                    @forelse ($alertItems as $a)
                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ $a['url'] }}">
                                <span><i class="fa-solid {{ $a['icon'] }} text-{{ $a['color'] }} ms-2"></i> {{ $a['label'] }}</span>
                                <span class="badge text-bg-{{ $a['color'] }} rounded-pill">{{ $a['count'] }}</span>
                            </a>
                        </li>
                    @empty
                        <li><span class="dropdown-item text-muted">لا توجد تنبيهات حالياً ✓</span></li>
                    @endforelse
                </ul>
            </div>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    @if ($u->avatar)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($u->avatar) }}" alt="" class="rounded-circle ms-1" style="width:24px;height:24px;object-fit:cover">
                    @else
                        <i class="fa-solid fa-user-circle ms-1"></i>
                    @endif
                    {{ $u->name }}
                    <span class="badge text-bg-secondary">{{ $u->getRoleNames()->first() }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fa-solid fa-user ms-1"></i> الملف الشخصي</a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="fa-solid fa-right-from-bracket ms-1"></i> تسجيل الخروج
                            </button>
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
