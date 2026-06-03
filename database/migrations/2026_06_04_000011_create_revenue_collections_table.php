<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تحصيلات الإيراد: دفعات جزئية على إيراد آجل (bank_account_id فارغ)
        Schema::create('revenue_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revenue_id')->constrained('revenues')->cascadeOnDelete();
            $table->date('collection_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('check_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('revenue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_collections');
    }
};
