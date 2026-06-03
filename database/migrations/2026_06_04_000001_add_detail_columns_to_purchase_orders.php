<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('tax', 15, 2)->default(0)->after('discount');
            $table->decimal('net_amount', 15, 2)->default(0)->after('tax');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('net_amount');
            $table->boolean('add_to_inventory')->default(false)->after('paid_amount');
            $table->date('actual_delivery')->nullable()->after('expected_delivery');
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['discount', 'tax', 'net_amount', 'paid_amount', 'add_to_inventory', 'actual_delivery', 'approved_at']);
        });
    }
};
