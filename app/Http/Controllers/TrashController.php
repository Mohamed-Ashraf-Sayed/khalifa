<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class TrashController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:users.view', only: ['index']),
            new Middleware('can:users.delete', only: ['restore', 'forceDelete']),
        ];
    }

    /** خريطة type-string → موديل (قائمة بيضاء للأمان). */
    private const TYPES = [
        'projects' => \App\Models\Project::class,
        'clients' => \App\Models\Client::class,
        'contractors' => \App\Models\Contractor::class,
        'suppliers' => \App\Models\Supplier::class,
        'employees' => \App\Models\Employee::class,
        'partners' => \App\Models\Partner::class,
        'invoices' => \App\Models\Invoice::class,
        'expenses' => \App\Models\Expense::class,
        'revenues' => \App\Models\Revenue::class,
        'purchase_orders' => \App\Models\PurchaseOrder::class,
        'contractor_extracts' => \App\Models\ContractorExtract::class,
        'materials' => \App\Models\Material::class,
    ];

    /** التسميات العربية لأنواع المحذوفات. */
    private const LABELS = [
        'projects' => 'المشاريع',
        'clients' => 'العملاء',
        'contractors' => 'المقاولون',
        'suppliers' => 'الموردون',
        'employees' => 'الموظفون',
        'partners' => 'الشركاء',
        'invoices' => 'الفواتير',
        'expenses' => 'المصروفات',
        'revenues' => 'الإيرادات',
        'purchase_orders' => 'أوامر الشراء',
        'contractor_extracts' => 'مستخلصات المقاولين',
        'materials' => 'المواد',
    ];

    public function index(Request $request): View
    {
        $type = (string) $request->input('type', 'projects');
        if (! isset(self::TYPES[$type])) {
            $type = 'projects';
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = self::TYPES[$type];

        $records = $modelClass::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(20)
            ->withQueryString();

        return view('trash.index', [
            'type' => $type,
            'records' => $records,
            'labels' => self::LABELS,
        ]);
    }

    public function restore(string $type, int $id): RedirectResponse
    {
        $modelClass = $this->resolveModel($type);

        $record = $modelClass::onlyTrashed()->findOrFail($id);
        $record->restore();

        return redirect()
            ->route('trash.index', ['type' => $type])
            ->with('success', 'تمت استعادة العنصر.');
    }

    public function forceDelete(string $type, int $id): RedirectResponse
    {
        $modelClass = $this->resolveModel($type);

        $record = $modelClass::onlyTrashed()->findOrFail($id);
        $record->forceDelete();

        return redirect()
            ->route('trash.index', ['type' => $type])
            ->with('success', 'تم حذف العنصر نهائياً.');
    }

    /** يحوّل نوع السلسلة إلى موديل عبر القائمة البيضاء، أو يرمي 404. */
    private function resolveModel(string $type): string
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }
}
