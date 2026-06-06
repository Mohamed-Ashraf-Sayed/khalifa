@extends('layouts.app')

@section('title', 'قيد ' . $entry->entry_number)

@section('content')
    @if (session('error'))
        <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation ms-1"></i> {{ session('error') }}</div>
    @endif

    @php($badge = $entry->status === 'posted' ? 'success' : 'secondary')

    <div class="row g-3 mb-3">
        <div class="col-md-7">
            <div class="card h-100"><div class="card-body">
                <h5 class="mb-1">قيد #{{ $entry->entry_number }}</h5>
                <div class="text-muted small mb-2">
                    <span class="badge text-bg-{{ $badge }}">{{ \App\Models\JournalEntry::STATUSES[$entry->status] ?? $entry->status }}</span>
                    · المصدر: {{ $entry->source }}
                </div>
                <div>الوصف: <strong>{{ $entry->description }}</strong></div>
                <div>التاريخ: {{ $entry->entry_date?->format('Y-m-d') }}</div>
                <div>أنشأه: {{ $entry->creator?->name ?? '—' }}</div>
                @if ($entry->status === 'posted')
                    <div>رحّله: {{ $entry->poster?->name ?? '—' }} @if($entry->posted_at) · {{ $entry->posted_at->format('Y-m-d H:i') }} @endif</div>
                @endif
                @if ($entry->reference_type)
                    <div class="mt-1">المرجع: <span class="badge text-bg-light">{{ $entry->reference_type }} #{{ $entry->reference_id }}</span></div>
                @endif
                @if ($entry->notes)
                    <div class="mt-2 text-muted small">{{ $entry->notes }}</div>
                @endif
            </div></div>
        </div>
        <div class="col-md-5">
            <div class="card h-100"><div class="card-body">
                <div class="row text-center">
                    <div class="col"><div class="text-muted small">إجمالي المدين</div><div class="fs-5 fw-bold">{{ number_format((float) $entry->total_debit, 2) }}</div></div>
                    <div class="col"><div class="text-muted small">إجمالي الدائن</div><div class="fs-5 fw-bold">{{ number_format((float) $entry->total_credit, 2) }}</div></div>
                    <div class="col">
                        <div class="text-muted small">التوازن</div>
                        <div>
                            @if ($entry->isBalanced())
                                <span class="badge text-bg-success">متوازن</span>
                            @else
                                <span class="badge text-bg-danger">غير متوازن</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">بنود القيد</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الحساب</th>
                            <th>بيان</th>
                            <th>المشروع</th>
                            <th>مركز التكلفة</th>
                            <th class="text-end">مدين</th>
                            <th class="text-end">دائن</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entry->lines as $line)
                            <tr>
                                <td>
                                    <span class="text-muted">{{ $line->account?->code }}</span>
                                    {{ $line->account?->name ?? '—' }}
                                </td>
                                <td>{{ $line->description ?? '—' }}</td>
                                <td>{{ $line->project?->name ?? '—' }}</td>
                                <td>{{ $line->costCenter?->name ?? '—' }}</td>
                                <td class="text-end">{{ bccomp((string) $line->debit, '0', 2) > 0 ? number_format((float) $line->debit, 2) : '—' }}</td>
                                <td class="text-end">{{ bccomp((string) $line->credit, '0', 2) > 0 ? number_format((float) $line->credit, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-start">الإجماليات</th>
                            <th class="text-end">{{ number_format((float) $entry->total_debit, 2) }}</th>
                            <th class="text-end">{{ number_format((float) $entry->total_credit, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex gap-2 mt-2 flex-wrap">
                <a href="{{ route('journal_entries.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>

                @can('accounting.edit')
                    @if ($entry->status === 'draft')
                        <a href="{{ route('journal_entries.edit', $entry) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
                        <form method="POST" action="{{ route('journal_entries.post', $entry) }}" class="d-inline" onsubmit="return confirm('ترحيل القيد؟')">
                            @csrf
                            <button class="btn btn-sm" style="background:#8b7355;color:#fff" @disabled(! $entry->isBalanced())><i class="fa-solid fa-circle-check ms-1"></i> ترحيل</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('journal_entries.unpost', $entry) }}" class="d-inline" onsubmit="return confirm('إلغاء ترحيل القيد؟')">
                            @csrf
                            <button class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-rotate-left ms-1"></i> إلغاء الترحيل</button>
                        </form>
                    @endif
                @endcan

                @can('accounting.delete')
                    @if ($entry->status === 'draft')
                        <form method="POST" action="{{ route('journal_entries.destroy', $entry) }}" class="d-inline" onsubmit="return confirm('حذف القيد؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash ms-1"></i> حذف</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </div>
@endsection
