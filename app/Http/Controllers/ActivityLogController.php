<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ActivityLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:users.view')]; // للمديرين فقط
    }

    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($modelType = $request->input('model_type')) {
            $query->where('model_type', 'like', "%{$modelType}%");
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(30)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);
        $actions = ActivityLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('activity_logs.index', compact('logs', 'users', 'actions'));
    }
}
