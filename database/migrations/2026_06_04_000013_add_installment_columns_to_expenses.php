<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
            $table->string('payment_status', 20)->default('paid')->after('paid_amount');
            $table->date('due_date')->nullable();
            $table->boolean('is_credit')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'payment_status', 'due_date', 'is_credit']);
        });
    }
};
