<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_extract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_extract_id')->constrained('contractor_extracts')->cascadeOnDelete();
            $table->string('description');                 // بند الأعمال
            $table->string('unit', 30)->nullable();         // الوحدة (م2/م3/طن...)
            $table->decimal('quantity', 15, 3)->default(0); // الكمية الحالية
            $table->decimal('prev_quantity', 15, 3)->default(0); // الكمية السابقة (تراكمي)
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0); // = quantity × unit_price
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('contractor_extract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_extract_items');
    }
};
