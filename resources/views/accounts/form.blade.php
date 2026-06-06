@extends('layouts.app')

@section('title', $account->exists ? 'تعديل حساب' : 'حساب جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $account->exists ? route('accounts.update', $account) : route('accounts.store') }}">
                @csrf
                @if ($account->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">الكود <span class="text-danger">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $account->code) }}" class="form-control font-monospace" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">اسم الحساب <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $account->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Models\Account::TYPES as $k => $label)
                                <option value="{{ $k }}" @selected(old('type', $account->type) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الحساب الأب</label>
                        <select name="parent_id" class="form-select">
                            <option value="">— بدون (حساب رئيسي) —</option>
                            @foreach ($parents as $p)
                                <option value="{{ $p->id }}" @selected((int) old('parent_id', $account->parent_id) === $p->id)>{{ $p->code }} — {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الطبيعة <span class="text-danger">*</span></label>
                        <select name="normal_balance" class="form-select" required>
                            @foreach (\App\Models\Account::NORMAL as $k => $label)
                                <option value="{{ $k }}" @selected(old('normal_balance', $account->normal_balance) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الرصيد الافتتاحي</label>
                        <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', $account->opening_balance ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <input type="text" name="description" value="{{ old('description', $account->description) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_group" value="0">
                            <input type="checkbox" name="is_group" value="1" class="form-check-input" id="is_group" @checked(old('is_group', $account->is_group))>
                            <label class="form-check-label" for="is_group">حساب تجميعي (لا يقبل قيوداً مباشرة)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $account->is_active ?? true))>
                            <label class="form-check-label" for="is_active">نشط</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
