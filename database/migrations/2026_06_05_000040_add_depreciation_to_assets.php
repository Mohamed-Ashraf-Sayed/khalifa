<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('salvage_value', 15, 2)->default(0)->after('purchase_value');   // قيمة الخردة
            $table->string('depreciation_method', 20)->default('straight_line')->after('depreciation_rate'); // straight_line | declining
            $table->date('disposal_date')->nullable()->after('status');     // تاريخ الاستبعاد/البيع
            $table->decimal('disposal_value', 15, 2)->nullable()->after('disposal_date'); // قيمة البيع/الاستبعاد
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['salvage_value', 'depreciation_method', 'disposal_date', 'disposal_value']);
        });
    }
};
