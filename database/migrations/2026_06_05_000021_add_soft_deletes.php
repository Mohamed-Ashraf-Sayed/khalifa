<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** الجداول التي يُضاف لها الحذف الناعم (deleted_at). */
    private const TABLES = [
        'projects', 'clients', 'contractors', 'suppliers', 'employees', 'partners',
        'invoices', 'expenses', 'revenues', 'purchase_orders', 'contractor_extracts', 'materials',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
