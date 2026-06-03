<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['expenses', 'revenues', 'project_costs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['expenses', 'revenues', 'project_costs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('cost_center_id');
            });
        }
    }
};
