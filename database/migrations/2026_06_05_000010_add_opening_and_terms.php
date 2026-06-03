<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('payment_terms')->nullable();
        });

        Schema::table('contractors', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0);
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'credit_limit', 'payment_terms']);
        });

        Schema::table('contractors', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
