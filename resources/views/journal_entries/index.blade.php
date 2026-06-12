@extends('layouts.app')

@section('title', 'قيود اليومية')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد القيود', number_format($stats['count']), 'fa-book-journal-whills', 'text-primary'],
            ['مسودات', number_format($stats['drafts']), 'fa-pen-ruler', 'text-warning'],
            ['مرحّلة', number_format($stats['posted']), 'fa-circle-check', 'text-success'],
            ['إجمالي المدين المرحّل', number_format($stats['posted_debit'], 2), 'fa-scale-balanced', 'text-info'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-3 col-6"><div class="statcard {{ str_replace('text-','sc-',$color) }} h-100">
                <span class="sc-ic"><i class="fa-solid {{ $icon }}"></i></span>
                <span><span class="sc-v d-block">{{ $v }}</span><span class="sc-l d-block">{{ $l }}</span></span>
            </div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('accounting.create')
                    <a href="{{ route('journal_entries.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> قيد جديد
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="رقم القيد أو الوصف">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\JournalEntry::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-12 col-md-auto">
                    <div class="filter-actions">
                        <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass ms-1"></i> بحث</button>
                        @if (request()->query())
                            <a href="{{ url()->current() }}" class="btn btn-light">مسح</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم القيد</th>
                            <th>التاريخ</th>
                            <th>الوصف</th>
                            <th>المدين</th>
                            <th>الدائن</th>
                            <th>المصدر</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php($badge = $entry->status === 'posted' ? 'success' : 'secondary')
                            <tr>
                                <td class="fw-semibold">{{ $entry->entry_number }}</td>
                                <td>{{ $entry->entry_date?->format('Y-m-d') }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($entry->description, 40) }}</td>
                                <td>{{ number_format((float) $entry->total_debit, 2) }}</td>
                                <td>{{ number_format((float) $entry->total_credit, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ $entry->source }}</span></td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\JournalEntry::STATUSES[$entry->status] ?? $entry->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('journal_entries.show', $entry) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('accounting.edit')
                                        @if ($entry->status === 'draft')
                                            <a href="{{ route('journal_entries.edit', $entry) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                        @endif
                                    @endcan
                                    @can('accounting.delete')
                                        @if ($entry->status === 'draft')
                                            <form method="POST" action="{{ route('journal_entries.destroy', $entry) }}" class="d-inline"
                                                  data-confirm="متأكد من حذف القيد؟">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد قيود بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $entries->links() }}
        </div>
    </div>
@endsection
