<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('project_contracts', 'signed_date')) {
                $table->date('signed_date')->nullable();
            }
            if (! Schema::hasColumn('project_contracts', 'advance_payment')) {
                $table->decimal('advance_payment', 15, 2)->default(0); // دفعة مقدمة
            }
            if (! Schema::hasColumn('project_contracts', 'retention_percent')) {
                $table->decimal('retention_percent', 5, 2)->default(0); // نسبة محتجز الضمان
            }
            if (! Schema::hasColumn('project_contracts', 'warranty_months')) {
                $table->integer('warranty_months')->nullable(); // مدة الضمان بالشهور
            }
            if (! Schema::hasColumn('project_contracts', 'consultant')) {
                $table->string('consultant')->nullable(); // الاستشاري
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_contracts', function (Blueprint $table) {
            foreach (['signed_date', 'advance_payment', 'retention_percent', 'warranty_months', 'consultant'] as $column) {
                if (Schema::hasColumn('project_contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
