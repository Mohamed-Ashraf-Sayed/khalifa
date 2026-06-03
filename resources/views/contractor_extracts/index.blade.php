@extends('layouts.app')

@section('title', 'مستخلصات المقاولين')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث برقم المستخلص">
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\ContractorExtract::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('contractors.create')
                    <a href="{{ route('contractor_extracts.create') }}" class="btn" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> مستخلص جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم المستخلص</th>
                            <th>المقاول</th>
                            <th>المشروع</th>
                            <th>الإجمالي</th>
                            <th>الخصومات</th>
                            <th>الصافي</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($extracts as $extract)
                            @php($badge = match($extract->status) {
                                'paid' => 'success', 'approved' => 'primary',
                                'partial' => 'info', 'cancelled' => 'danger', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $extract->extract_number }}</td>
                                <td>{{ $extract->contractor?->name ?? '—' }}</td>
                                <td>{{ $extract->project?->name ?? '—' }}</td>
                                <td>{{ number_format($extract->total_amount, 2) }}</td>
                                <td class="text-danger">{{ number_format($extract->deductions, 2) }}</td>
                                <td class="fw-bold">{{ number_format($extract->net_amount, 2) }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\ContractorExtract::STATUSES[$extract->status] ?? $extract->status }}</span></td>
                                <td class="text-end">
                                    @can('contractors.edit')
                                        <a href="{{ route('contractor_extracts.edit', $extract) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('contractors.delete')
                                        <form method="POST" action="{{ route('contractor_extracts.destroy', $extract) }}" class="d-inline"
                                              onsubmit="return confirm('حذف المستخلص؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد مستخلصات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $extracts->links() }}
        </div>
    </div>
@endsection
