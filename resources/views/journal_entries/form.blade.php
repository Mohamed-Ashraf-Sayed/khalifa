@extends('layouts.app')

@section('title', $entry->exists ? 'تعديل قيد' : 'قيد جديد')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation ms-1"></i> {{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $entry->exists ? route('journal_entries.update', $entry) : route('journal_entries.store') }}">
                @csrf
                @if ($entry->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">تاريخ القيد <span class="text-danger">*</span></label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', $entry->entry_date?->format('Y-m-d') ?? now()->toDateString()) }}" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">الوصف <span class="text-danger">*</span></label>
                        <input type="text" name="description" value="{{ old('description', $entry->description) }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $entry->notes) }}</textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">بنود القيد</h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addJvLine()"><i class="fa-solid fa-plus ms-1"></i> إضافة بند</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="jvLines">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:200px">الحساب <span class="text-danger">*</span></th>
                                <th style="min-width:120px">مدين</th>
                                <th style="min-width:120px">دائن</th>
                                <th style="min-width:150px">المشروع</th>
                                <th style="min-width:150px">مركز التكلفة</th>
                                <th style="min-width:160px">بيان</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="jvBody"></tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th class="text-start">الإجماليات</th>
                                <th><span id="sumDebit">0.00</span></th>
                                <th><span id="sumCredit">0.00</span></th>
                                <th colspan="4">
                                    <span id="balanceBadge" class="badge text-bg-secondary">—</span>
                                    <span class="small text-muted ms-2">الفرق: <span id="diffVal">0.00</span></span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('journal_entries.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

    @php($accountOptions = '')
    @foreach ($accounts as $acc)
        @php($accountOptions .= '<option value="'.$acc->id.'">'.e($acc->code.' - '.$acc->name).'</option>')
    @endforeach
    @php($projectOptions = '')
    @foreach ($projects as $p)
        @php($projectOptions .= '<option value="'.$p->id.'">'.e($p->name).'</option>')
    @endforeach
    @php($ccOptions = '')
    @foreach ($costCenters as $cc)
        @php($ccOptions .= '<option value="'.$cc->id.'">'.e($cc->name).'</option>')
    @endforeach

    <script>
        const ACC_OPTS = `{!! $accountOptions !!}`;
        const PROJ_OPTS = `{!! $projectOptions !!}`;
        const CC_OPTS = `{!! $ccOptions !!}`;
        let jvIdx = 0;

        function addJvLine(data) {
            data = data || {};
            const i = jvIdx++;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><select name="lines[${i}][account_id]" class="form-select form-select-sm"><option value="">— اختر —</option>${ACC_OPTS}</select></td>
                <td><input type="number" step="0.01" min="0" name="lines[${i}][debit]" class="form-control form-control-sm jv-debit" value="${data.debit ?? ''}"></td>
                <td><input type="number" step="0.01" min="0" name="lines[${i}][credit]" class="form-control form-control-sm jv-credit" value="${data.credit ?? ''}"></td>
                <td><select name="lines[${i}][project_id]" class="form-select form-select-sm"><option value="">— بدون —</option>${PROJ_OPTS}</select></td>
                <td><select name="lines[${i}][cost_center_id]" class="form-select form-select-sm"><option value="">— بدون —</option>${CC_OPTS}</select></td>
                <td><input type="text" name="lines[${i}][description]" class="form-control form-control-sm" value="${data.description ?? ''}"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove();recalcJv();"><i class="fa-solid fa-xmark"></i></button></td>`;
            document.getElementById('jvBody').appendChild(tr);
            if (data.account_id) tr.querySelector('select[name$="[account_id]"]').value = data.account_id;
            if (data.project_id) tr.querySelector('select[name$="[project_id]"]').value = data.project_id;
            if (data.cost_center_id) tr.querySelector('select[name$="[cost_center_id]"]').value = data.cost_center_id;
            tr.querySelectorAll('.jv-debit, .jv-credit').forEach(el => el.addEventListener('input', function () {
                // مدين أو دائن — مش الاتنين
                const row = this.closest('tr');
                if (this.classList.contains('jv-debit') && this.value) row.querySelector('.jv-credit').value = '';
                if (this.classList.contains('jv-credit') && this.value) row.querySelector('.jv-debit').value = '';
                recalcJv();
            }));
            recalcJv();
        }

        function recalcJv() {
            let d = 0, c = 0;
            document.querySelectorAll('.jv-debit').forEach(el => d += parseFloat(el.value || 0));
            document.querySelectorAll('.jv-credit').forEach(el => c += parseFloat(el.value || 0));
            document.getElementById('sumDebit').textContent = d.toFixed(2);
            document.getElementById('sumCredit').textContent = c.toFixed(2);
            const diff = Math.abs(d - c);
            document.getElementById('diffVal').textContent = diff.toFixed(2);
            const badge = document.getElementById('balanceBadge');
            if (d > 0 && diff < 0.005) {
                badge.className = 'badge text-bg-success';
                badge.textContent = 'متوازن';
            } else {
                badge.className = 'badge text-bg-danger';
                badge.textContent = 'غير متوازن';
            }
        }

        const SEED = @json(old('lines') ?: $seedLines);

        if (SEED && SEED.length) {
            SEED.forEach(l => addJvLine(l));
        } else {
            addJvLine();
            addJvLine();
        }
    </script>
@endsection
