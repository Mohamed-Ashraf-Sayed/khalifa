<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->decimal('stock_before', 15, 2)->default(0);
            $table->decimal('stock_after', 15, 2)->default(0);
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('to_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('warehouse_location', 100)->nullable();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['to_project_id']);
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->dropColumn([
                'unit_price',
                'total_value',
                'stock_before',
                'stock_after',
                'employee_id',
                'to_project_id',
                'reference_type',
                'reference_id',
                'warehouse_location',
            ]);
        });
    }
};
