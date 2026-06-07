@extends('layouts.app')

@section('title', 'قائمة الدخل (محاسبي)')

@push('styles')
<style>
    .fs-doc { max-width: 920px; margin: 0 auto; }
    .fs-head { text-align: center; margin-bottom: 1.2rem; }
    .fs-head .co { font-size: 1.15rem; font-weight: 800; color: var(--ink); }
    .fs-head .sub { color: var(--muted); font-size: .85rem; margin-top: 2px; }
    .fs-head .title { font-weight: 800; margin-top: .5rem; font-size: 1.05rem; }
    .fs-meta { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; font-size: .8rem; color: var(--ink-2); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); padding: .4rem 0; margin-bottom: 1rem; }
    table.fs { width: 100%; border-collapse: collapse; }
    table.fs th { font-size: .82rem; color: var(--muted); font-weight: 700; padding: .5rem .6rem; border-bottom: 2px solid var(--line); text-align: center; }
    table.fs th.b { text-align: start; }
    table.fs td { padding: .5rem .6rem; font-size: .9rem; vertical-align: middle; }
    table.fs td.b { text-align: start; }
    table.fs td.num { text-align: center; font-variant-numeric: tabular-nums; white-space: nowrap; min-width: 130px; }
    .fs-line td { border-bottom: 1px dashed var(--line-2); }
    .fs-header-row td { font-weight: 700; color: var(--brown-dark); padding-top: .7rem; }
    .fs-total td { border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); font-weight: 800; background: var(--bg); }
    .fs-grand td { border-top: 2px double var(--ink); border-bottom: 2px double var(--ink); font-weight: 800; }
    .neg { color: var(--danger); }
    .fs-sign { display: flex; justify-content: space-between; margin-top: 3rem; font-size: .9rem; font-weight: 700; }
</style>
@endpush

@section('content')
    @php
        $renderCell = function ($value, $ln) {
            $value = (string) ($value ?? '0');
            if (bccomp($value, '0', 2) === 0 && ($ln['blank_if_zero'] ?? false)) {
                return '<span class="text-muted">—</span>';
            }
            $deduct = $ln['deduct'] ?? false;
            $isNeg = bccomp($value, '0', 2) < 0;
            $abs = $isNeg ? bcmul($value, '-1', 2) : $value;
            $txt = number_format((float) $abs, 0);
            if ($deduct || $isNeg) {
                return '<span class="neg">(' . $txt . ')</span>';
            }
            return $txt;
        };
    @endphp

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end mb-3 no-print">
        <form method="GET" class="d-flex gap-2 align-items-end">
            <div>
                <label class="form-label small mb-1">السنة المالية</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <div class="d-flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

    <div class="card"><div class="card-body">
        <div class="fs-doc">
            <div class="fs-head">
                <div class="co">{{ $company['name'] }}</div>
                @if ($company['legal_form'])<div class="sub">{{ $company['legal_form'] }}</div>@endif
                <div class="title">قائمة الدخل</div>
                <div class="sub">عن السنة المالية المنتهية في 31 ديسمبر {{ $year }}</div>
            </div>

            <div class="fs-meta">
                <span>السجل التجاري: {{ $company['commercial_register'] ?: '—' }}</span>
                <span>الرقم القومي/الضريبي للمنشأة: {{ $company['tax_number'] ?: '—' }}</span>
            </div>

            <table class="fs">
                <thead>
                    <tr>
                        <th class="b">بيان</th>
                        <th style="width:60px">إيضاح</th>
                        <th>31 ديسمبر {{ $year }}</th>
                        <th>31 ديسمبر {{ $priorYear }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $ln)
                        @if ($ln['type'] === 'header')
                            <tr class="fs-header-row"><td class="b" colspan="4">{{ $ln['label'] }}</td></tr>
                        @else
                            @php($rowClass = $ln['type'] === 'total' ? 'fs-total' : ($ln['type'] === 'grand' ? 'fs-grand' : 'fs-line'))
                            <tr class="{{ $rowClass }}">
                                <td class="b">{{ $ln['label'] }}</td>
                                <td></td>
                                <td class="num">{!! $renderCell($cur[$ln['key']] ?? '0', $ln) !!}</td>
                                <td class="num">{!! $renderCell($prior[$ln['key']] ?? '0', $ln) !!}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <div class="fs-sign">
                <span>المدير المالي</span>
                <span>رئيس مجلس الإدارة</span>
            </div>
        </div>
    </div></div>
@endsection
