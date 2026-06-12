@extends('layouts.app')

@section('title', 'دليل الحسابات')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الحسابات', number_format($stats['count']), 'fa-sitemap', 'text-primary'],
            ['حسابات تجميعية', number_format($stats['groups']), 'fa-folder-tree', 'text-info'],
            ['حسابات فرعية', number_format($stats['leaves']), 'fa-file-invoice', 'text-success'],
            ['غير نشطة', number_format($stats['inactive']), 'fa-ban', 'text-secondary'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-3 col-6"><div class="statcard {{ str_replace('text-','sc-',$color) }} h-100">
                <span class="sc-ic"><i class="fa-solid {{ $icon }}"></i></span>
                <span><span class="sc-v d-block">{{ $v }}</span><span class="sc-l d-block">{{ $l }}</span></span>
            </div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="mb-0"><i class="fa-solid fa-sitemap ms-1"></i> شجرة الحسابات</h5>
                @can('accounting.create')
                    <a href="{{ route('accounts.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> حساب جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الكود</th>
                            <th>اسم الحساب</th>
                            <th>الطبيعة</th>
                            <th class="text-end">الرصيد</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (\App\Models\Account::TYPES as $typeKey => $typeLabel)
                            @php($typeAccounts = $accounts->where('type', $typeKey)->whereNull('parent_id'))
                            @if ($typeAccounts->isNotEmpty())
                                <tr class="table-secondary">
                                    <td colspan="5" class="fw-bold">{{ $typeLabel }}</td>
                                </tr>
                                @foreach ($typeAccounts as $root)
                                    @include('accounts._row', ['account' => $root, 'depth' => 0, 'byParent' => $byParent])
                                @endforeach
                            @endif
                        @empty
                        @endforelse
                        @if ($accounts->isEmpty())
                            <tr><td colspan="5" class="text-center text-muted py-4">لا توجد حسابات بعد.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
