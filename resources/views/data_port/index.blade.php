@extends('layouts.app')

@section('title', 'استيراد / تصدير البيانات')

@section('content')
    <div class="row g-3">
        {{-- تصدير --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fa-solid fa-file-export ms-1"></i> تصدير البيانات</h5>
                    <p class="text-muted small">اختر الكيان لتصدير بياناته بصيغة CSV أو تحميل قالب فارغ.</p>
                    <form id="exportForm">
                        <div class="mb-3">
                            <label class="form-label">الكيان</label>
                            <select id="exportEntity" class="form-select">
                                @foreach ($entities as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a id="exportLink" href="{{ route('data_port.export', ['entity' => $entities->keys()->first()]) }}"
                               class="btn" style="background:#2b4c80;color:#fff">
                                <i class="fa-solid fa-download ms-1"></i> تصدير CSV
                            </a>
                            <a id="templateLink" href="{{ route('data_port.template', ['entity' => $entities->keys()->first()]) }}"
                               class="btn btn-outline-secondary">
                                <i class="fa-solid fa-file-csv ms-1"></i> تحميل قالب
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- استيراد --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fa-solid fa-file-import ms-1"></i> استيراد البيانات</h5>
                    <p class="text-muted small">ارفع ملف CSV مطابق للقالب (أول صف = أسماء الأعمدة).</p>
                    <form id="importForm" method="POST" action="{{ route('data_port.import', ['entity' => $entities->keys()->first()]) }}"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">الكيان</label>
                            <select id="importEntity" class="form-select">
                                @foreach ($entities as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ملف CSV</label>
                            <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                        </div>
                        <button type="submit" class="btn" style="background:#2b4c80;color:#fff">
                            <i class="fa-solid fa-upload ms-1"></i> رفع واستيراد
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- سجل عمليات الاستيراد --}}
    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="fa-solid fa-clock-rotate-left ms-1"></i> آخر عمليات الاستيراد</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الكيان</th>
                            <th>الملف</th>
                            <th>إجمالي</th>
                            <th>تم استيراده</th>
                            <th>فشل</th>
                            <th>ملاحظات / أخطاء</th>
                            <th>المستخدم</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="fw-semibold">{{ $entities[$log->entity] ?? $log->entity }}</td>
                                <td>{{ $log->file_name }}</td>
                                <td>{{ $log->total_rows }}</td>
                                <td><span class="badge text-bg-success">{{ $log->imported_rows }}</span></td>
                                <td>
                                    @if ($log->failed_rows > 0)
                                        <span class="badge text-bg-danger">{{ $log->failed_rows }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">0</span>
                                    @endif
                                </td>
                                <td style="max-width:320px">
                                    @if ($log->notes)
                                        <div class="small text-danger" style="white-space:pre-wrap">{{ $log->notes }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $log->user?->name ?? '—' }}</td>
                                <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد عمليات استيراد بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const exportEntity = document.getElementById('exportEntity');
            const exportLink = document.getElementById('exportLink');
            const templateLink = document.getElementById('templateLink');
            const importEntity = document.getElementById('importEntity');
            const importForm = document.getElementById('importForm');

            const exportBase = "{{ route('data_port.export', ['entity' => '__ENTITY__']) }}";
            const templateBase = "{{ route('data_port.template', ['entity' => '__ENTITY__']) }}";
            const importBase = "{{ route('data_port.import', ['entity' => '__ENTITY__']) }}";

            exportEntity.addEventListener('change', function () {
                exportLink.href = exportBase.replace('__ENTITY__', this.value);
                templateLink.href = templateBase.replace('__ENTITY__', this.value);
            });

            importEntity.addEventListener('change', function () {
                importForm.action = importBase.replace('__ENTITY__', this.value);
            });
        })();
    </script>
@endsection
