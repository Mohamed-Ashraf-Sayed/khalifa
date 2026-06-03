@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <h4 class="mb-1">أهلاً، {{ auth()->user()->name }} 👋</h4>
            <p class="text-muted mb-3">
                دورك في النظام:
                <span class="badge text-bg-secondary">{{ auth()->user()->getRoleNames()->first() }}</span>
            </p>
            <p class="mb-0">
                الأساس الآمن جاهز. الوحدات (المشاريع، العملاء، الماليات...) هتظهر في القائمة الجانبية
                حسب صلاحياتك وهنبنيها تباعاً فوق الأساس ده.
            </p>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <i class="fa-solid fa-shield-halved text-success fa-lg ms-1"></i>
                <strong>صلاحياتك:</strong> {{ auth()->user()->getAllPermissions()->count() }} صلاحية
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <i class="fa-solid fa-user-shield text-primary fa-lg ms-1"></i>
                <strong>الأدوار في النظام:</strong> {{ \Spatie\Permission\Models\Role::count() }}
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <i class="fa-solid fa-list-check text-warning fa-lg ms-1"></i>
                <strong>إجمالي الصلاحيات:</strong> {{ \Spatie\Permission\Models\Permission::count() }}
            </div></div>
        </div>
    </div>
@endsection
