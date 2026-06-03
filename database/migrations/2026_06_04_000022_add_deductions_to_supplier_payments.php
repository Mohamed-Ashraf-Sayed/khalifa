<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->decimal('vat', 15, 2)->default(0)->after('amount');
            $table->decimal('insurance_5_percent', 15, 2)->default(0)->after('vat');
            $table->decimal('social_insurance', 15, 2)->default(0)->after('insurance_5_percent');
            $table->decimal('commercial_profit_supply', 15, 2)->default(0)->after('social_insurance');
            $table->decimal('commercial_profit_works', 15, 2)->default(0)->after('commercial_profit_supply');
            $table->decimal('engineering_professions', 15, 2)->default(0)->after('commercial_profit_works');
            $table->decimal('arts_specialists', 15, 2)->default(0)->after('engineering_professions');
            $table->decimal('applied_professions', 15, 2)->default(0)->after('arts_specialists');
            $table->decimal('bank_transfer_fee', 15, 2)->default(0)->after('applied_professions');
            $table->decimal('other_deductions', 15, 2)->default(0)->after('bank_transfer_fee');
            $table->decimal('total_deductions', 15, 2)->default(0)->after('other_deductions');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropColumn([
                'vat',
                'insurance_5_percent',
                'social_insurance',
                'commercial_profit_supply',
                'commercial_profit_works',
                'engineering_professions',
                'arts_specialists',
                'applied_professions',
                'bank_transfer_fee',
                'other_deductions',
                'total_deductions',
            ]);
        });
    }
};
