<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ActivityLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:users.view')]; // للمديرين فقط
    }

    public function index(): View
    {
        $logs = ActivityLog::with('user')->latest('created_at')->paginate(30);

        return view('activity_logs.index', compact('logs'));
    }
}
