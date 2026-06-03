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
            'column' => 'name',
            'route' => 'projects.show',
            'label' => 'المشاريع',
            'display' => 'name',
            'icon' => 'fa-diagram-project',
        ],
        'clients' => [
            'model' => Client::class,
            'permission' => 'clients.view',
            'column' => 'name',
            'route' => 'clients.show',
            'label' => 'العملاء',
            'display' => 'name',
            'icon' => 'fa-user-tie',
        ],
        'contractors' => [
            'model' => Contractor::class,
            'permission' => 'contractors.view',
            'column' => 'name',
            'route' => 'contractors.show',
            'label' => 'المقاولون',
            'display' => 'name',
            'icon' => 'fa-helmet-safety',
        ],
        'suppliers' => [
            'model' => Supplier::class,
            'permission' => 'suppliers.view',
            'column' => 'name',
            'route' => 'suppliers.show',
            'label' => 'الموردون',
            'display' => 'name',
            'icon' => 'fa-truck',
        ],
        'employees' => [
            'model' => Employee::class,
            'permission' => 'employees.view',
            'column' => 'name',
            'route' => 'employees.show',
            'label' => 'الموظفون',
            'display' => 'name',
            'icon' => 'fa-id-badge',
        ],
        'invoices' => [
            'model' => Invoice::class,
            'permission' => 'invoices.view',
            'column' => 'invoice_number',
            'route' => 'invoices.show',
            'label' => 'الفواتير',
            'display' => 'invoice_number',
            'icon' => 'fa-file-invoice',
        ],
        'purchase_orders' => [
            'model' => PurchaseOrder::class,
            'permission' => 'purchase_orders.view',
            'column' => 'order_number',
            'route' => 'purchase_orders.show',
            'label' => 'أوامر الشراء',
            'display' => 'order_number',
            'icon' => 'fa-cart-shopping',
        ],
        'contractor_extracts' => [
            'model' => ContractorExtract::class,
            'permission' => 'contractors.view',
            'column' => 'extract_number',
            'route' => 'contractor_extracts.show',
            'label' => 'مستخلصات المقاولين',
            'display' => 'extract_number',
            'icon' => 'fa-file-contract',
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
                    ->where($cfg['column'], 'like', '%'.$q.'%')
                    ->limit(8)
                    ->get();

                if ($hits->isNotEmpty()) {
                    $groups[$key] = [
                        'label' => $cfg['label'],
                        'route' => $cfg['route'],
                        'display' => $cfg['display'],
                        'icon' => $cfg['icon'],
                        'hits' => $hits,
                    ];
                }
            }
        }

        return view('search.results', [
            'q' => $q,
            'groups' => $groups,
        ]);
    }
}
