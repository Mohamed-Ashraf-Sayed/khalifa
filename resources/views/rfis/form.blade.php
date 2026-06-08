@extends('layouts.app')

@section('title', $rfi->exists ? 'تعديل طلب استفسار' : 'طلب استفسار جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $rfi->exists ? route('rfis.update', $rfi) : route('rfis.store') }}">
                @csrf
                @if ($rfi->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم الطلب</label>
                        <input type="text" value="{{ $rfiNumber }}" class="form-control" disabled>
                        <div class="form-text">يُولّد تلقائياً</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">— اختر المشروع —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $rfi->project_id) === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">الموضوع <span class="text-danger">*</span></label>
                        <input type="text" name="subject" value="{{ old('subject', $rfi->subject) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">موجَّه إلى</label>
                        <input type="text" name="raised_to" value="{{ old('raised_to', $rfi->raised_to) }}" class="form-control" placeholder="الاستشاري / المهندس">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">موعد الرد المستهدف</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $rfi->due_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الاستفسار <span class="text-danger">*</span></label>
                        <textarea name="question" rows="4" class="form-control" required>{{ old('question', $rfi->question) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('rfis.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
