@extends('layouts.app')

@section('title', 'سنة مالية جديدة')

@section('content')
    <div class="card" style="max-width:520px"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-calendar-plus ms-1" style="color:#8b7355"></i> إنشاء سنة مالية</h6>
        <form method="POST" action="{{ route('fiscal_years.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">السنة <span class="text-danger">*</span></label>
                <input type="number" name="year" value="{{ old('year', $suggested) }}" min="2000" max="2100" class="form-control" required>
                <div class="form-text">هيتم إنشاء 12 فترة شهرية تلقائياً (يناير–ديسمبر).</div>
                @error('year')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-check ms-1"></i> إنشاء</button>
            <a href="{{ route('fiscal_years.index') }}" class="btn btn-light">رجوع</a>
        </form>
    </div></div>
@endsection
