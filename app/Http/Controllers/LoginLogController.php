<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LoginLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:users.view')]; // للمديرين فقط
    }

    public function index(Request $request): View
    {
        $query = LoginAttempt::query()->latest();

        if ($email = $request->input('email')) {
            $query->where('email', 'like', "%{$email}%");
        }

        if ($ip = $request->input('ip_address')) {
            $query->where('ip_address', 'like', "%{$ip}%");
        }

        if ($status = $request->input('status')) {
            if ($status === 'success') {
                $query->where('successful', true);
            } elseif ($status === 'fail') {
                $query->where('successful', false);
            }
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => LoginAttempt::count(),
            'successful' => LoginAttempt::where('successful', true)->count(),
            'failed' => LoginAttempt::where('successful', false)->count(),
            'distinct_ips' => LoginAttempt::distinct('ip_address')->count('ip_address'),
        ];

        return view('login_logs.index', compact('logs', 'stats'));
    }
}
