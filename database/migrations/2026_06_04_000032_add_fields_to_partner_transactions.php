<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_transactions', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('check_number', 50)->nullable();
            $table->foreignId('partner_deposit_id')->nullable()->constrained('partner_deposits')->nullOnDelete();
            $table->string('profit_period', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('partner_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropConstrainedForeignId('partner_deposit_id');
            $table->dropColumn(['payment_method', 'check_number', 'profit_period']);
        });
    }
};
