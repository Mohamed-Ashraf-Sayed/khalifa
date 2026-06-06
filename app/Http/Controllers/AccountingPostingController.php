<?php

namespace App\Http\Controllers;

use App\Services\JournalPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class AccountingPostingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:accounting.view', only: ['index']),
            new Middleware('can:accounting.edit', only: ['generate']),
        ];
    }

    public function index(JournalPostingService $posting): View
    {
        $rows = $posting->counts();

        return view('accounting.posting', compact('rows'));
    }

    public function generate(Request $request, JournalPostingService $posting): RedirectResponse
    {
        $type = (string) $request->input('type', 'all');
        $userId = $request->user()->id;

        if ($type === 'all') {
            $res = $posting->generateAll($userId);
            $posted = array_sum(array_column($res, 'posted'));
        } else {
            $r = $posting->generateForType($type, $userId);
            $posted = $r['posted'];
        }

        return back()->with('success', "تم توليد وترحيل {$posted} قيد محاسبي من المستندات.");
    }
}
