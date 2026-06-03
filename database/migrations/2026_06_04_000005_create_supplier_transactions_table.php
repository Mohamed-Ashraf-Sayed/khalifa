<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // دفتر توريدات/مشتريات المورّد التفصيلية (مواد ومعدات) — مستقل عن أوامر الشراء الرسمية
        Schema::create('supplier_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('transaction_date');
            $table->string('item_description');            // البيان
            $table->string('category', 50)->nullable();    // الفئة (مواد/معدات/خدمات...)
            $table->string('unit', 30)->nullable();
            $table->decimal('quantity', 15, 3)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);   // = quantity × unit_price
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);     // بعد الخصم
            $table->decimal('paid_amount', 15, 2)->default(0);    // مدفوع عند الشراء
            $table->string('payment_method', 30)->nullable();
            $table->string('check_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_transactions');
    }
};
