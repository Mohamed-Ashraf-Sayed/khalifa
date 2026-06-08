@extends('layouts.app')

@section('title', $meeting->exists ? 'تعديل محضر اجتماع' : 'محضر اجتماع جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $meeting->exists ? route('meetings.update', $meeting) : route('meetings.store') }}">
                @csrf
                @if ($meeting->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم المحضر</label>
                        <input type="text" value="{{ $meetingNumber }}" class="form-control" disabled>
                        <div class="form-text">يُولّد تلقائياً</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">العنوان <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $meeting->title) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— بدون مشروع —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $meeting->project_id) === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ الاجتماع <span class="text-danger">*</span></label>
                        <input type="date" name="meeting_date" value="{{ old('meeting_date', $meeting->meeting_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ الاجتماع القادم</label>
                        <input type="date" name="next_meeting_date" value="{{ old('next_meeting_date', $meeting->next_meeting_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المكان</label>
                        <input type="text" name="location" value="{{ old('location', $meeting->location) }}" class="form-control" placeholder="مكتب المشروع / قاعة الاجتماعات">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الحضور</label>
                        <textarea name="attendees" rows="3" class="form-control" placeholder="أسماء الحضور...">{{ old('attendees', $meeting->attendees) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">جدول الأعمال</label>
                        <textarea name="agenda" rows="4" class="form-control">{{ old('agenda', $meeting->agenda) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">القرارات</label>
                        <textarea name="decisions" rows="4" class="form-control">{{ old('decisions', $meeting->decisions) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">بنود المتابعة</label>
                        <textarea name="action_items" rows="4" class="form-control">{{ old('action_items', $meeting->action_items) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('meetings.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
