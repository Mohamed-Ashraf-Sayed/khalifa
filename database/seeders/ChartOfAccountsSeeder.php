<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * شجرة الحسابات القياسية لشركات المقاولات.
     * idempotent عبر firstOrCreate بالكود، ويُربط parent_id آلياً ببادئة الكود:
     * أكواد 4 خانات أبوها بادئة الخانتين، وأكواد الخانتين أبوها الخانة الواحدة.
     */
    public function run(): void
    {
        // [code, name, type, normal_balance, is_group]
        $chart = [
            ['1', 'الأصول', 'asset', 'debit', true],
            ['11', 'الأصول المتداولة', 'asset', 'debit', true],
            ['1101', 'النقدية بالخزينة', 'asset', 'debit', false],
            ['1102', 'النقدية بالبنوك', 'asset', 'debit', false],
            ['1103', 'العملاء (المدينون)', 'asset', 'debit', false],
            ['1104', 'أوراق القبض', 'asset', 'debit', false],
            ['1105', 'المخزون', 'asset', 'debit', false],
            ['1106', 'أعمال تحت التنفيذ', 'asset', 'debit', false],
            ['1107', 'عُهد وسلف الموظفين', 'asset', 'debit', false],
            ['1108', 'ضريبة القيمة المضافة (مدخلات)', 'asset', 'debit', false],
            ['12', 'الأصول الثابتة', 'asset', 'debit', true],
            ['1201', 'الأصول الثابتة', 'asset', 'debit', false],
            ['1202', 'مجمع إهلاك الأصول', 'asset', 'credit', false],
            ['2', 'الالتزامات', 'liability', 'credit', true],
            ['21', 'الالتزامات المتداولة', 'liability', 'credit', true],
            ['2101', 'الموردون (الدائنون)', 'liability', 'credit', false],
            ['2102', 'المقاولون من الباطن', 'liability', 'credit', false],
            ['2103', 'أوراق الدفع', 'liability', 'credit', false],
            ['2104', 'ضريبة القيمة المضافة (مخرجات)', 'liability', 'credit', false],
            ['2105', 'ضرائب ودمغة مستحقة', 'liability', 'credit', false],
            ['2106', 'رواتب مستحقة', 'liability', 'credit', false],
            ['2107', 'محتجزات ضمان أعمال', 'liability', 'credit', false],
            ['3', 'حقوق الملكية', 'equity', 'credit', true],
            ['3101', 'رأس المال', 'equity', 'credit', false],
            ['3102', 'جاري الشركاء', 'equity', 'credit', false],
            ['3103', 'الأرباح المرحّلة', 'equity', 'credit', false],
            ['4', 'الإيرادات', 'revenue', 'credit', true],
            ['4101', 'إيرادات عقود المقاولات', 'revenue', 'credit', false],
            ['4102', 'إيرادات أخرى', 'revenue', 'credit', false],
            ['5', 'المصروفات', 'expense', 'debit', true],
            ['51', 'تكاليف المشاريع المباشرة', 'expense', 'debit', true],
            ['5101', 'مستخلصات المقاولين', 'expense', 'debit', false],
            ['5102', 'مشتريات ومواد المشروع', 'expense', 'debit', false],
            ['5103', 'أجور عمالة المشروع', 'expense', 'debit', false],
            ['5104', 'معدات وإيجارات الموقع', 'expense', 'debit', false],
            ['52', 'مصروفات إدارية وعمومية', 'expense', 'debit', true],
            ['5201', 'الرواتب والأجور', 'expense', 'debit', false],
            ['5202', 'إيجارات', 'expense', 'debit', false],
            ['5203', 'مرافق (كهرباء/مياه/اتصالات)', 'expense', 'debit', false],
            ['5204', 'مصروفات عمومية أخرى', 'expense', 'debit', false],
            ['5205', 'مصروف إهلاك الأصول', 'expense', 'debit', false],
        ];

        // إنشاء الحسابات أولاً (مرتّبة بطول الكود لضمان وجود الأب قبل ربطه).
        foreach ($chart as [$code, $name, $type, $normal, $isGroup]) {
            Account::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normal,
                    'is_group' => $isGroup,
                    'is_active' => true,
                    'opening_balance' => 0,
                ]
            );
        }

        // ربط parent_id ببادئة الكود.
        foreach ($chart as [$code]) {
            $parentCode = $this->parentCode($code);
            if ($parentCode === null) {
                continue;
            }

            $account = Account::where('code', $code)->first();
            $parent = Account::where('code', $parentCode)->first();
            if ($account && $parent && $account->parent_id !== $parent->id) {
                $account->parent_id = $parent->id;
                $account->save();
            }
        }
    }

    /** بادئة كود الأب: 4 خانات => خانتين، خانتين => خانة، خانة => بدون أب. */
    private function parentCode(string $code): ?string
    {
        return match (strlen($code)) {
            4 => substr($code, 0, 2),
            2 => substr($code, 0, 1),
            default => null,
        };
    }
}
