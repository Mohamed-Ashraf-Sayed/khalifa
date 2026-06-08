@extends('layouts.app')

@section('title', 'الترحيل المحاسبي التلقائي')

@section('content')
    <div class="card mb-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
                <h6 class="mb-1"><i class="fa-solid fa-wand-magic-sparkles ms-1" style="color:#8b7355"></i> الترحيل المحاسبي التلقائي</h6>
                <p class="text-muted small mb-0">يولّد قيود يومية متوازنة من المستندات التشغيلية (لا يتكرّر — كل مستند يُرحَّل مرّة واحدة). يلزم وجود دليل الحسابات.</p>
            </div>
            @can('accounting.edit')
                <form method="POST" action="{{ route('accounting.posting.generate') }}" data-confirm="توليد القيود لكل المستندات غير المُرحّلة؟">
                    @csrf
                    <input type="hidden" name="type" value="all">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-bolt ms-1"></i> توليد قيود الكل</button>
                </form>
            @endcan
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>نوع المستند</th><th>عدد المستندات</th><th>المُرحَّل</th><th>المتبقّي</th><th class="text-end">إجراء</th></tr></thead>
                <tbody>
                    @foreach ($rows as $type => $r)
                        @php($remaining = max(0, $r['total'] - $r['posted']))
                        <tr>
                            <td class="fw-semibold">{{ $r['label'] }}</td>
                            <td>{{ $r['total'] }}</td>
                            <td><span class="badge text-bg-success">{{ $r['posted'] }}</span></td>
                            <td>@if ($remaining > 0)<span class="badge text-bg-warning">{{ $remaining }}</span>@else<span class="text-muted">—</span>@endif</td>
                            <td class="text-end">
                                @can('accounting.edit')
                                    <form method="POST" action="{{ route('accounting.posting.generate') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $type }}">
                                        <button class="btn btn-sm btn-outline-secondary" {{ $remaining > 0 ? '' : 'disabled' }}><i class="fa-solid fa-arrow-right-to-bracket ms-1"></i> ترحيل</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="alert alert-info border mt-2 mb-0 small">
            <i class="fa-solid fa-circle-info ms-1"></i>
            الاعتراف بالإيراد من الفواتير، والتحصيل من دفعات الفواتير. المصروف النقدي مقابل الخزينة/البنك والآجل مقابل الموردين. المستخلص: مدين تكلفة المقاولين / دائن محتجزات الضمان + المستحق للمقاول. القيود المولّدة تظهر في «قيود اليومية» بمصدر «تلقائي» ويمكن مراجعتها.
        </div>
    </div></div>
@endsection
