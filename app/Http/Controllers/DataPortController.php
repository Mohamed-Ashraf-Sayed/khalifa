<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ImportLog;
use App\Models\Project;
use App\Models\Revenue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataPortController extends Controller implements HasMiddleware
{
    /**
     * خريطة الكيانات القابلة للاستيراد/التصدير.
     * كل كيان: model (FQCN) + label عربي + columns المسموح بها (حقول scalar آمنة).
     */
    private const MAP = [
        'clients' => [
            'model' => Client::class,
            'label' => 'العملاء',
            'columns' => ['name', 'company_name', 'phone', 'email', 'address', 'city', 'tax_number'],
        ],
        'contractors' => [
            'model' => Contractor::class,
            'label' => 'المقاولون',
            'columns' => ['contractor_code', 'name', 'company_name', 'phone', 'email', 'specialty', 'national_id', 'tax_number'],
        ],
        'projects' => [
            'model' => Project::class,
            'label' => 'المشاريع',
            'columns' => ['name', 'status', 'start_date', 'end_date', 'contract_value'],
        ],
        'employees' => [
            'model' => Employee::class,
            'label' => 'الموظفون',
            'columns' => ['employee_code', 'name', 'national_id', 'job_title', 'department', 'salary', 'phone', 'email', 'hire_date'],
        ],
        'expenses' => [
            'model' => Expense::class,
            'label' => 'المصروفات',
            'columns' => ['category', 'description', 'amount', 'expense_date', 'payment_method'],
        ],
        'revenues' => [
            'model' => Revenue::class,
            'label' => 'الإيرادات',
            'columns' => ['description', 'amount', 'revenue_date', 'payment_method'],
        ],
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('can:settings.view', only: ['index']),
            new Middleware('can:settings.edit', only: ['export', 'template', 'import']),
        ];
    }

    public function index(): View
    {
        $entities = collect(self::MAP)->map(fn ($cfg) => $cfg['label']);
        $logs = ImportLog::with('user')->latest()->limit(20)->get();

        return view('data_port.index', compact('entities', 'logs'));
    }

    public function template(string $entity): StreamedResponse
    {
        $config = $this->resolve($entity);
        $filename = $entity.'_template.csv';

        return response()->streamDownload(function () use ($config) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM لضمان العرض الصحيح للعربية في Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $config['columns']);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function export(string $entity): StreamedResponse
    {
        $config = $this->resolve($entity);
        $columns = $config['columns'];
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = $config['model'];
        $rows = $model::query()->get();
        $filename = $entity.'_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM لضمان العرض الصحيح للعربية في Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                $line = [];
                foreach ($columns as $col) {
                    $value = $row->{$col};
                    if ($value instanceof \DateTimeInterface) {
                        $value = $value->format('Y-m-d');
                    }
                    $line[] = $value;
                }
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request, string $entity): RedirectResponse
    {
        $config = $this->resolve($entity);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $allowed = $config['columns'];
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = $config['model'];
        $table = (new $model)->getTable();
        $hasCreatedBy = Schema::hasColumn($table, 'created_by');

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        $imported = 0;
        $failed = 0;
        $total = 0;
        $header = null;
        $rowNumber = 0;        // رقم الصف داخل الملف (للترويسة + البيانات)
        $errors = [];          // رسائل أخطاء الصفوف الفاشلة لتُحفظ في notes

        if ($handle !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // تخطّي الصفوف الفارغة تماماً
                if ($data === [null] || (count($data) === 1 && trim((string) $data[0]) === '')) {
                    continue;
                }

                if ($header === null) {
                    // أول صف = الترويسة. نزيل الـBOM من أول خلية.
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]);
                    $header = array_map(fn ($h) => trim((string) $h), $data);
                    continue;
                }

                $total++;

                try {
                    $mapped = [];
                    foreach ($header as $index => $colName) {
                        // نتجاهل الأعمدة غير المعروفة
                        if (! in_array($colName, $allowed, true)) {
                            continue;
                        }
                        $mapped[$colName] = isset($data[$index]) ? trim((string) $data[$index]) : null;
                    }

                    if ($mapped === []) {
                        $failed++;
                        $errors[] = "صف {$rowNumber}: لا توجد أعمدة معروفة قابلة للاستيراد.";
                        continue;
                    }

                    if ($hasCreatedBy) {
                        $mapped['created_by'] = $request->user()->id;
                    }

                    $model::create($mapped);
                    $imported++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "صف {$rowNumber}: ".$e->getMessage();
                }
            }
            fclose($handle);
        }

        // نحفظ أول 20 رسالة خطأ كحد أقصى لتجنّب امتلاء العمود
        $notes = null;
        if ($errors !== []) {
            $shown = array_slice($errors, 0, 20);
            $notes = implode("\n", $shown);
            if (count($errors) > 20) {
                $notes .= "\n… و".(count($errors) - 20).' أخطاء أخرى.';
            }
        }

        ImportLog::create([
            'entity' => $entity,
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $total,
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'user_id' => $request->user()->id,
            'notes' => $notes,
        ]);

        return redirect()->route('data_port.index')
            ->with('success', "تم استيراد {$imported} صف، فشل {$failed}.");
    }

    /**
     * يرجّع إعدادات الكيان أو يُلقي 404 لو غير معروف.
     *
     * @return array{model:class-string,label:string,columns:array<int,string>}
     */
    private function resolve(string $entity): array
    {
        abort_unless(array_key_exists($entity, self::MAP), 404);

        return self::MAP[$entity];
    }
}
