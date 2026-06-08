@extends('layouts.app')

@section('title', $changeOrder->exists ? 'تعديل أمر تغيير' : 'أمر تغيير جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $changeOrder->exists ? route('change_orders.update', $changeOrder) : route('change_orders.store') }}">
                @csrf
                @if ($changeOrder->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم الأمر</label>
                        <input type="text" value="{{ $coNumber }}" class="form-control" disabled>
                        <div class="form-text">يُولّد تلقائياً</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="change_type" class="form-select">
                            @foreach (\App\Models\ChangeOrder::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('change_type', $changeOrder->change_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\ChangeOrder::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $changeOrder->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" id="co-project" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $changeOrder->project_id) === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العقد</label>
                        <select name="contract_id" id="co-contract" class="form-select" data-selected="{{ old('contract_id', $changeOrder->contract_id) }}">
                            <option value="">— بدون —</option>
                        </select>
                        <div class="form-text">العقود المتاحة تخص المشروع المحدد فقط</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">العنوان <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $changeOrder->title) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">القيمة <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $changeOrder->amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الطلب <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" value="{{ old('request_date', $changeOrder->request_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $changeOrder->description) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('change_orders.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

    @php($contractsJson = $contracts->map(fn ($c) => [
        'id' => $c->id,
        'project_id' => $c->project_id,
        'label' => $c->contract_number.' — '.$c->title,
    ])->values())

    <script>
        const CO_CONTRACTS = @json($contractsJson);

        function coRefreshContracts() {
            const projectSel = document.getElementById('co-project');
            const contractSel = document.getElementById('co-contract');
            if (!projectSel || !contractSel) {
                return;
            }

            const projectId = projectSel.value;
            const selected = contractSel.dataset.selected || '';

            contractSel.innerHTML = '<option value="">— بدون —</option>';
            CO_CONTRACTS
                .filter(c => String(c.project_id) === String(projectId))
                .forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.label;
                    if (String(c.id) === String(selected)) {
                        opt.selected = true;
                    }
                    contractSel.appendChild(opt);
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const projectSel = document.getElementById('co-project');
            if (projectSel) {
                projectSel.addEventListener('change', coRefreshContracts);
            }
            coRefreshContracts();
        });
    </script>
@endsection
