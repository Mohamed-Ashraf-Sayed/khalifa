@extends('layouts.app')

@section('title', 'قائمة الدخل')

@push('styles')
<style>
    .fs-doc { max-width: 960px; margin: 0 auto; color: var(--ink); }
    .fs-brand { text-align: center; margin-bottom: .5rem; }
    .fs-brand .logo { width: 64px; height: 64px; display: inline-flex; align-items: center; justify-content: center; color: var(--brown); margin-bottom: .5rem; }
    .fs-brand .logo img, .fs-brand .logo svg { max-width: 100%; max-height: 100%; }
    .fs-brand .co { font-size: 1.35rem; font-weight: 800; letter-spacing: -.01em; }
    .fs-brand .lf { color: var(--muted); font-size: .85rem; }
    .fs-title { text-align: center; margin: .9rem 0 .2rem; }
    .fs-title .t { font-size: 1.15rem; font-weight: 800; position: relative; display: inline-block; padding-bottom: .35rem; }
    .fs-title .t::after { content: ''; position: absolute; inset-inline: 20%; bottom: 0; height: 3px; border-radius: 3px; background: var(--brown); }
    .fs-title .p { color: var(--muted); font-size: .85rem; margin-top: .4rem; }
    .fs-meta { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; font-size: .8rem; color: var(--ink-2);
        border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); padding: .5rem .2rem; margin: 1rem 0 1.2rem; }
    .fs-meta b { color: var(--ink); }

    table.fs { width: 100%; border-collapse: collapse; }
    table.fs thead th { font-size: .78rem; color: #fff; background: var(--brown-dark); font-weight: 700; padding: .6rem .7rem; text-align: center; }
    table.fs thead th.b { text-align: start; }
    table.fs thead th:first-child { border-start-start-radius: 8px; }
    table.fs thead th:last-child { border-start-end-radius: 8px; }
    table.fs td { padding: .5rem .7rem; font-size: .9rem; vertical-align: middle; }
    table.fs td.b { text-align: start; }
    table.fs td.num { text-align: center; font-variant-numeric: tabular-nums; white-space: nowrap; min-width: 120px; }
    table.fs td.note, table.fs td.pct { text-align: center; color: var(--muted); font-size: .8rem; }
    .fs-line:nth-of-type(even) td { background: #fcfbf9; }
    .fs-line td { border-bottom: 1px dashed var(--line-2); }
    .fs-line td.b { padding-inline-start: 1.2rem; }
    .fs-header-row td { font-weight: 800; color: var(--brown-dark); padding-top: .8rem; background: #fff !important; }
    .fs-total td { border-top: 1px solid var(--brown-light); border-bottom: 1px solid var(--brown-light); font-weight: 800; background: var(--brown-50) !important; }
    .fs-grand td { border-top: 2px double var(--ink); border-bottom: 2px double var(--ink); font-weight: 800; background: var(--brown-50) !important; }
    .neg { color: var(--danger); }
    .pos { color: var(--success); }
    .fs-sign { display: flex; justify-content: space-between; margin-top: 3.5rem; font-size: .9rem; }
    .fs-sign .s { text-align: center; }
    .fs-sign .s .role { font-weight: 700; margin-bottom: 2.5rem; }
    .fs-sign .s .line { border-top: 1px solid var(--ink); width: 160px; }
    @media print { .fs-doc { max-width: 100%; } table.fs thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
@endpush

@section('content')
    @php
        $fmt = function ($value, $ln) {
            $value = (string) ($value ?? '0');
            if (bccomp($value, '0', 2) === 0 && ($ln['blank_if_zero'] ?? false)) {
                return '<span class="text-muted">—</span>';
            }
            $deduct = $ln['deduct'] ?? false;
            $isNeg = bccomp($value, '0', 2) < 0;
            $abs = $isNeg ? bcmul($value, '-1', 2) : $value;
            $txt = number_format((float) $abs, 0);
            return ($deduct || $isNeg) ? '<span class="neg">(' . $txt . ')</span>' : $txt;
        };
        $pct = function ($value) use ($cur) {
            $base = (string) ($cur['activity_revenue'] ?? '0');
            if (bccomp($base, '0', 2) === 0) return '—';
            $p = bcmul(bcdiv((string) $value, $base, 4), '100', 1);
            return number_format((float) $p, 1) . '%';
        };
        $note = 0;
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

    <div class="card"><div class="card-body py-4">
        <div class="fs-doc">
            <div class="fs-brand">
                <div class="logo">@include('partials.logo')</div>
                <div class="co">{{ $company['name'] }}</div>
                @if ($company['legal_form'])<div class="lf">{{ $company['legal_form'] }}</div>@endif
            </div>
            <div class="fs-title">
                <div class="t">قائمة الدخل</div>
                <div class="p">عن السنة المالية المنتهية في 31 ديسمبر {{ $year }} · جميع المبالغ بالـ{{ $company['currency'] }}</div>
            </div>

            @if ($company['commercial_register'] || $company['tax_number'])
                <div class="fs-meta">
                    @if ($company['commercial_register'])<span>السجل التجاري: <b>{{ $company['commercial_register'] }}</b></span>@endif
                    @if ($company['tax_number'])<span>الرقم القومي/الضريبي للمنشأة: <b>{{ $company['tax_number'] }}</b></span>@endif
                </div>
            @else
                <div class="mb-3"></div>
            @endif

            <table class="fs">
                <thead>
                    <tr>
                        <th class="b">بيان</th>
                        <th style="width:55px">إيضاح</th>
                        <th>31 ديسمبر {{ $year }}</th>
                        <th style="width:70px">%</th>
                        <th>31 ديسمبر {{ $priorYear }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $ln)
                        @if ($ln['type'] === 'header')
                            <tr class="fs-header-row"><td class="b" colspan="5">{{ $ln['label'] }}</td></tr>
                        @else
                            @php($rowClass = $ln['type'] === 'total' ? 'fs-total' : ($ln['type'] === 'grand' ? 'fs-grand' : 'fs-line'))
                            @php($isLine = $ln['type'] === 'line')
                            @php($noteNo = $isLine ? ++$note : '')
                            <tr class="{{ $rowClass }}">
                                <td class="b">{{ $ln['label'] }}</td>
                                <td class="note">{{ $noteNo }}</td>
                                <td class="num">{!! $fmt($cur[$ln['key']] ?? '0', $ln) !!}</td>
                                <td class="pct">{{ ($isLine && bccomp((string) ($cur[$ln['key']] ?? '0'), '0', 2) === 0 && ($ln['blank_if_zero'] ?? false)) ? '' : $pct($cur[$ln['key']] ?? '0') }}</td>
                                <td class="num">{!! $fmt($prior[$ln['key']] ?? '0', $ln) !!}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <div class="fs-sign">
                <div class="s"><div class="role">المدير المالي</div><div class="line"></div></div>
                <div class="s"><div class="role">رئيس مجلس الإدارة</div><div class="line"></div></div>
            </div>
        </div>
    </div></div>
@endsection
