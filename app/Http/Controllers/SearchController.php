<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\ContractorExtract;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * بحث شامل عبر الكيانات الأساسية.
     * كل كيان: صلاحية العرض المطلوبة، العمود المبحوث فيه، واسم مسار العرض.
     */
    private const ENTITIES = [
        'projects' => [
            'model' => Project::class,
            'permission' => 'projects.view',
            'columns' => ['name', 'location'],
            'route' => 'projects.show',
            'label' => 'المشاريع',
            'display' => 'name',
            'icon' => 'fa-diagram-project',
            'with' => ['client'],
        ],
        'clients' => [
            'model' => Client::class,
            'permission' => 'clients.view',
            'columns' => ['name', 'company_name', 'phone', 'phone2', 'email'],
            'route' => 'clients.show',
            'label' => 'العملاء',
            'display' => 'name',
            'icon' => 'fa-user-tie',
        ],
        'contractors' => [
            'model' => Contractor::class,
            'permission' => 'contractors.view',
            'columns' => ['contractor_code', 'name', 'company_name', 'phone', 'phone2', 'email'],
            'route' => 'contractors.show',
            'label' => 'المقاولون',
            'display' => 'name',
            'icon' => 'fa-helmet-safety',
        ],
        'suppliers' => [
            'model' => Supplier::class,
            'permission' => 'suppliers.view',
            'columns' => ['name', 'company_name', 'phone', 'phone2', 'email'],
            'route' => 'suppliers.show',
            'label' => 'الموردون',
            'display' => 'name',
            'icon' => 'fa-truck',
        ],
        'employees' => [
            'model' => Employee::class,
            'permission' => 'employees.view',
            'columns' => ['name'],
            'route' => 'employees.show',
            'label' => 'الموظفون',
            'display' => 'name',
            'icon' => 'fa-id-badge',
        ],
        'invoices' => [
            'model' => Invoice::class,
            'permission' => 'invoices.view',
            'columns' => ['invoice_number'],
            'route' => 'invoices.show',
            'label' => 'الفواتير',
            'display' => 'invoice_number',
            'icon' => 'fa-file-invoice',
            'with' => ['client'],
        ],
        'purchase_orders' => [
            'model' => PurchaseOrder::class,
            'permission' => 'purchase_orders.view',
            'columns' => ['order_number'],
            'route' => 'purchase_orders.show',
            'label' => 'أوامر الشراء',
            'display' => 'order_number',
            'icon' => 'fa-cart-shopping',
            'with' => ['supplier'],
        ],
        'contractor_extracts' => [
            'model' => ContractorExtract::class,
            'permission' => 'contractors.view',
            'columns' => ['extract_number'],
            'route' => 'contractor_extracts.show',
            'label' => 'مستخلصات المقاولين',
            'display' => 'extract_number',
            'icon' => 'fa-file-contract',
            'with' => ['contractor', 'project'],
        ],
    ];

    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $groups = [];

        if ($q !== '') {
            foreach (self::ENTITIES as $key => $cfg) {
                // يتجاهل أي كيان لا يملك المستخدم صلاحية عرضه
                if (! Gate::allows($cfg['permission'])) {
                    continue;
                }

                $hits = $cfg['model']::query()
                    ->with($cfg['with'] ?? [])
                    ->where(function ($query) use ($cfg, $q) {
                        foreach ($cfg['columns'] as $column) {
                            $query->orWhere($column, 'like', '%'.$q.'%');
                        }
                    })
                    ->limit(8)
                    ->get();

                if ($hits->isNotEmpty()) {
                    $groups[$key] = [
                        'label' => $cfg['label'],
                        'route' => $cfg['route'],
                        'display' => $cfg['display'],
                        'icon' => $cfg['icon'],
                        'hits' => $hits->map(fn ($hit) => [
                            'model' => $hit,
                            'display' => $hit->{$cfg['display']} ?? '—',
                            'subtitle' => $this->subtitle($key, $hit),
                        ]),
                    ];
                }
            }
        }

        return view('search.results', [
            'q' => $q,
            'groups' => $groups,
        ]);
    }

    /**
     * سطر سياقي ثانوي لكل نتيجة بحث (قراءة فقط، يعرض قيم محسوبة مسبقاً)
     * لتمييز السجلات المتشابهة قبل الدخول عليها.
     */
    private function subtitle(string $key, $hit): ?string
    {
        return match ($key) {
            'projects' => $hit->client?->name,
            'clients', 'suppliers' => $hit->company_name ?: $hit->phone,
            'contractors' => $hit->contractor_code
                ? trim($hit->contractor_code.' · '.($hit->company_name ?: $hit->phone), ' ·')
                : ($hit->company_name ?: $hit->phone),
            'invoices' => trim(($hit->client?->name ?? '').' · '.number_format((float) $hit->total_amount, 2).' ج.م', ' ·'),
            'purchase_orders' => $hit->supplier?->name,
            'contractor_extracts' => trim(($hit->contractor?->name ?? '').' · '.($hit->project?->name ?? ''), ' ·'),
            default => null,
        };
    }
}
