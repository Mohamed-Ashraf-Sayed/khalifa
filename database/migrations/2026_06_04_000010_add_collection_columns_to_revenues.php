<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
            $table->string('payment_status', 20)->default('pending')->after('paid_amount');
            $table->date('due_date')->nullable();
            $table->string('check_number', 50)->nullable();
            $table->boolean('deferred_check')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'payment_status', 'due_date', 'check_number', 'deferred_check']);
        });
    }
};
