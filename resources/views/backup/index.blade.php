@extends('layouts.app')

@section('title', 'النسخ الاحتياطي')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-1"><i class="fa-solid fa-database ms-1"></i> النسخ الاحتياطي لقاعدة البيانات</h5>
                    <p class="text-muted small mb-0">أنشئ نسخة احتياطية من قاعدة البيانات أو حمّل نسخة سابقة.</p>
                </div>
                @can('settings.edit')
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('backups.run') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn" style="background:#2b4c80;color:#fff">
                                <i class="fa-solid fa-database ms-1"></i> نسخة قاعدة البيانات
                            </button>
                        </form>
                        <form method="POST" action="{{ route('backups.run') }}" class="m-0">
                            @csrf
                            <input type="hidden" name="full" value="1">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fa-solid fa-box-archive ms-1"></i> نسخة كاملة (ملفات + قاعدة)
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="fa-solid fa-box-archive ms-1"></i> النسخ المتاحة</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الملف</th>
                            <th>الحجم</th>
                            <th>التاريخ</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backups as $backup)
                            <tr>
                                <td class="fw-semibold" dir="ltr">{{ $backup['name'] }}</td>
                                <td>{{ number_format($backup['size'] / 1048576, 2) }} م.ب</td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp($backup['date'])->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    @can('settings.edit')
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('backups.download', ['file' => $backup['name']]) }}"
                                               class="btn btn-sm" style="background:#2b4c80;color:#fff">
                                                <i class="fa-solid fa-download ms-1"></i> تحميل
                                            </a>
                                            <form method="POST" action="{{ route('backups.destroy', ['file' => $backup['name']]) }}"
                                                  class="m-0" data-confirm="حذف النسخة الاحتياطية {{ $backup['name'] }}؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash ms-1"></i> حذف
                                                </button>
                                            </form>
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">لا توجد نسخ احتياطية بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
