@extends('layouts.app')

@section('title', 'نتائج البحث')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0"><i class="fa-solid fa-magnifying-glass ms-1"></i> نتائج البحث</h5>
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('search') }}" class="d-flex gap-2">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="ابحث في المشاريع، العملاء، الفواتير، المستخلصات..." autofocus>
            <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div></div>

    @if ($q === '')
        <div class="alert alert-light border text-center text-muted">اكتب كلمة للبحث في كل أقسام النظام.</div>
    @elseif (empty($groups))
        <div class="alert alert-warning text-center">لا توجد نتائج مطابقة لـ "<strong>{{ $q }}</strong>".</div>
    @else
        @foreach ($groups as $group)
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">
                    <i class="fa-solid {{ $group['icon'] }} ms-1"></i> {{ $group['label'] }}
                    <span class="badge text-bg-light ms-1">{{ $group['hits']->count() }}</span>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($group['hits'] as $hit)
                        <a href="{{ route($group['route'], $hit) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span>{{ $hit->{$group['display']} ?? '—' }}</span>
                            <i class="fa-solid fa-chevron-left text-muted"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
@endsection
