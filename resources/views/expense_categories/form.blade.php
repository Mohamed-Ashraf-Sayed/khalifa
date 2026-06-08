@extends('layouts.app')

@section('title', $category->exists ? 'تعديل فئة' : 'فئة جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $category->exists ? route('expense_categories.update', $category) : route('expense_categories.store') }}">
                @csrf
                @if ($category->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الكود <span class="text-danger">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $category->code) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', $category->is_active ?? true))>
                            <label for="is_active" class="form-check-label">نشط</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('expense_categories.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
