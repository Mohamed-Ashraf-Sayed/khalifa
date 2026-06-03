<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->decimal('amount', 15, 2); // رأس المال
            $table->date('deposit_date');
            $table->decimal('profit_rate', 5, 2); // نسبة ربح سنوية %
            $table->integer('duration_months');
            $table->string('payout_frequency', 20); // monthly/quarterly/semiannual/annual
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_deposits');
    }
};
