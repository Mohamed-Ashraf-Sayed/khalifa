<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_profit_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_deposit_id')->constrained('partner_deposits')->cascadeOnDelete();
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_paid')->default(false);
            $table->date('paid_date')->nullable();
            $table->foreignId('partner_transaction_id')->nullable()->constrained('partner_transactions')->nullOnDelete();
            $table->timestamps();

            $table->index('partner_deposit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_profit_schedules');
    }
};
