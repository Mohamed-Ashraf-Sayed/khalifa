<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('category', 40)->nullable();
            $table->string('beneficiary', 150)->nullable();
            $table->string('check_number', 50)->nullable();
            $table->date('value_date')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->string('attachment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn(['category', 'beneficiary', 'check_number', 'value_date', 'is_reconciled', 'attachment']);
        });
    }
};
