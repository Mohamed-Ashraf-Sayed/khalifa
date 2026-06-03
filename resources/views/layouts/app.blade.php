<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') — {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --beige: #f5f1e8; --beige-dark: #e8e0cf; --brown: #8b7355; --brown-dark: #6f5b43;
        }
        * { font-family: 'Cairo', sans-serif; }
        body { background: var(--beige); color: #3a342b; }
        .sidebar {
            background: linear-gradient(180deg, var(--brown), var(--brown-dark));
            height: 100vh; width: 260px; position: fixed; inset-inline-start: 0; top: 0;
            overflow-y: auto; overflow-x: hidden;
        }
        /* شريط تمرير رفيع وأنيق داخل القائمة */
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.25); border-radius: 3px; }
        .sidebar .brand { position: sticky; top: 0; z-index: 2; background: var(--brown-dark); }
        .sidebar .brand { color: #fff; font-weight: 700; padding: 1.25rem; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,.15); }
        .sidebar .nav-link { color: rgba(255,255,255,.85); border-radius: .5rem; margin: .15rem .6rem; padding: .6rem .9rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,.15); color: #fff; }
        .sidebar .nav-link i { width: 22px; }
        .sidebar .nav-section { color: rgba(255,255,255,.45); font-size: .72rem; font-weight: 700; letter-spacing: .5px; padding: 1rem 1.4rem .3rem; text-transform: none; }
        .sidebar .nav-link { font-size: .92rem; }
        .main { margin-inline-start: 260px; }
        .topbar { background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.06); position: sticky; top: 0; z-index: 10; }
        .card { border: none; box-shadow: 0 2px 10px rgba(139,115,85,.08); border-radius: .8rem; }
        @media (max-width: 768px) { .sidebar { inset-inline-start: -260px; } .main { margin-inline-start: 0; } }
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
                @can('users.view')      <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="fa-solid fa-user-gear"></i> المستخدمون</a> @endcan
                @can('users.view')      <a class="nav-link {{ request()->routeIs('activity_logs.*') ? 'active' : '' }}" href="{{ route('activity_logs.index') }}"><i class="fa-solid fa-clock-rotate-left"></i> سجل النشاطات</a> @endcan
                @can('users.view')      <a class="nav-link {{ request()->routeIs('login_logs.*') ? 'active' : '' }}" href="{{ route('login_logs.index') }}"><i class="fa-solid fa-right-to-bracket"></i> سجل الدخول</a> @endcan
                @can('settings.view')   <a class="nav-link {{ request()->routeIs('data_port.*') ? 'active' : '' }}" href="{{ route('data_port.index') }}"><i class="fa-solid fa-file-import"></i> استيراد/تصدير</a> @endcan
                @can('settings.view')   <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}"><i class="fa-solid fa-gear"></i> الإعدادات</a> @endcan
            @endcanany
        </nav>
    </aside>

    <div class="main">
        <header class="topbar d-flex align-items-center justify-content-between px-4 py-2 mb-4">
            <h5 class="m-0">@yield('title', 'لوحة التحكم')</h5>
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
    @stack('scripts')
</body>
</html>
