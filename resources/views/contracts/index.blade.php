@extends('layouts.app')

@section('title', 'عقود المشاريع')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('contracts.create')
                    <a href="{{ route('contracts.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> عقد جديد
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بعنوان أو رقم العقد">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\ProjectContract::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
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
                            <th>رقم العقد</th>
                            <th>العنوان</th>
                            <th>المشروع</th>
                            <th>قيمة العقد</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contracts as $contract)
                            @php($badge = match($contract->status) {
                                'completed' => 'success', 'active' => 'primary',
                                'suspended' => 'warning', 'cancelled' => 'danger', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $contract->contract_number }}</td>
                                <td>{{ $contract->title }}</td>
                                <td>{{ $contract->project?->name ?? '—' }}</td>
                                <td>{{ number_format($contract->contract_value, 2) }} ج</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\ProjectContract::STATUSES[$contract->status] ?? $contract->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('contracts.edit')
                                        <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('contracts.delete')
                                        <form method="POST" action="{{ route('contracts.destroy', $contract) }}" class="d-inline"
                                              data-confirm="متأكد من حذف العقد؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد عقود بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $contracts->links() }}
        </div>
    </div>
@endsection
