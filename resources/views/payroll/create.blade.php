@extends('layouts.app')

@section('title', 'مسيّر رواتب جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('payroll.store') }}">
                @csrf

                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info ms-1"></i>
                    عند الحفظ سيتم توليد بند راتب لكل موظف نشط تلقائياً، مع خصم رصيد السلف إن وُجد.
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">السنة <span class="text-danger">*</span></label>
                        <input type="number" name="period_year" value="{{ old('period_year', $run->period_year) }}" class="form-control" min="2000" max="2100" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الشهر <span class="text-danger">*</span></label>
                        <select name="period_month" class="form-select" required>
                            @foreach (\App\Models\PayrollRun::MONTHS as $num => $label)
                                <option value="{{ $num }}" @selected((int) old('period_month', $run->period_month) === $num)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $run->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> إنشاء وتوليد البنود</button>
                    <a href="{{ route('payroll.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
