<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // اسم الحساب
            $table->string('bank_name');
            $table->string('account_number', 50)->nullable();
            $table->string('iban', 50)->nullable();
            $table->string('branch')->nullable();
            $table->string('currency', 10)->default('EGP');
            $table->decimal('opening_balance', 15, 2)->default(0);
            // الرصيد الحالي = رصيد افتتاحي + الإيداعات − السحوبات. مخزّن كـcache
            // ويُحدَّث داخل transaction، ويمكن إعادة اشتقاقه بدقّة من الحركات.
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
