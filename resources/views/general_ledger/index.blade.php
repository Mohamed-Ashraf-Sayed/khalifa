@extends('layouts.app')

@section('title', 'دفتر الأستاذ')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0"><i class="fa-solid fa-book ms-1"></i> دفتر الأستاذ</h5>
        <span class="text-muted small">افتح كشف حساب أي طرف (مع رصيد جارٍ)</span>
    </div>

    @php
        $cards = [
            [
                'title' => 'الحسابات البنكية',
                'icon' => 'fa-building-columns',
                'items' => $bankAccounts,
                'route' => 'bank_accounts.show',
                'param' => 'bank_account',
                'empty' => 'لا توجد حسابات بنكية.',
                'label' => fn ($a) => $a->name . ($a->bank_name ? ' — ' . $a->bank_name : ''),
            ],
            [
                'title' => 'الموردون',
                'icon' => 'fa-truck-field',
                'items' => $suppliers,
                'route' => 'suppliers.statement',
                'param' => 'supplier',
                'empty' => 'لا يوجد موردون.',
                'label' => fn ($s) => $s->name . ($s->company_name ? ' — ' . $s->company_name : ''),
            ],
            [
                'title' => 'المقاولون',
                'icon' => 'fa-helmet-safety',
                'items' => $contractors,
                'route' => 'contractors.statement',
                'param' => 'contractor',
                'empty' => 'لا يوجد مقاولون.',
                'label' => fn ($c) => $c->name . ($c->company_name ? ' — ' . $c->company_name : ''),
            ],
            [
                'title' => 'الشركاء',
                'icon' => 'fa-handshake',
                'items' => $partners,
                'route' => 'partners.statement',
                'param' => 'partner',
                'empty' => 'لا يوجد شركاء.',
                'label' => fn ($p) => $p->name,
            ],
            [
                'title' => 'الموظفون',
                'icon' => 'fa-id-badge',
                'items' => $employees,
                'route' => 'employees.statement',
                'param' => 'employee',
                'empty' => 'لا يوجد موظفون.',
                'label' => fn ($e) => $e->name . ($e->job_title ? ' — ' . $e->job_title : ''),
            ],
            [
                'title' => 'العملاء',
                'icon' => 'fa-user-tie',
                'items' => $clients,
                'route' => 'clients.statement',
                'param' => 'client',
                'empty' => 'لا يوجد عملاء.',
                'label' => fn ($c) => $c->name . ($c->company_name ? ' — ' . $c->company_name : ''),
            ],
            [
                'title' => 'المشاريع',
                'icon' => 'fa-diagram-project',
                'items' => $projects,
                'route' => 'general_ledger.project',
                'param' => 'project',
                'empty' => 'لا توجد مشاريع.',
                'label' => fn ($p) => $p->name,
            ],
        ];
    @endphp

    <div class="row g-3">
        @foreach ($cards as $card)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa-solid {{ $card['icon'] }} ms-1" style="color:#2b4c80"></i> {{ $card['title'] }}</h6>
                        @if ($card['items']->isEmpty())
                            <div class="text-muted small py-2">{{ $card['empty'] }}</div>
                        @else
                            <form onsubmit="event.preventDefault(); openStatement(this);" data-route="{{ $card['route'] }}" data-param="{{ $card['param'] }}" class="d-flex gap-2">
                                <select class="form-select form-select-sm" required>
                                    <option value="" disabled selected>اختر…</option>
                                    @foreach ($card['items'] as $item)
                                        <option value="{{ $item->id }}">{{ $card['label']($item) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm flex-shrink-0" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-arrow-left ms-1"></i> فتح الكشف</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @php
        $routeTemplates = [];
        foreach ($cards as $card) {
            $routeTemplates[$card['route']] = route($card['route'], [$card['param'] => '__ID__']);
        }
    @endphp

    <script>
        const ledgerRoutes = @json($routeTemplates);
        function openStatement(form) {
            const route = form.dataset.route;
            const id = form.querySelector('select').value;
            if (!id) return;
            const url = ledgerRoutes[route].replace('__ID__', id);
            window.location.href = url;
        }
    </script>
@endsection
