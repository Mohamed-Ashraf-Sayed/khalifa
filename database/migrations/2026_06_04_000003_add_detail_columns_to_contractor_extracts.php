<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractor_extracts', function (Blueprint $table) {
            $table->decimal('additions', 15, 2)->default(0)->after('total_amount');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('additions');
            $table->decimal('execution_percent', 5, 2)->default(0)->after('discount_percent');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('net_amount');
            $table->string('attachment')->nullable()->after('notes');
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('contractor_extracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['additions', 'discount_percent', 'execution_percent', 'paid_amount', 'attachment', 'approved_at']);
        });
    }
};
