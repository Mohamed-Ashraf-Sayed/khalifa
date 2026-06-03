<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // دفتر تكاليف المشاريع موزّعة على بنود الأعمال والجهات (مقاولين/موردين) — إدخال يدوي
        Schema::create('project_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('work_item');                          // بند الأعمال
            $table->string('contractor_supplier')->nullable();    // اسم المقاول/المورد
            $table->string('category')->nullable();
            $table->string('description')->nullable();
            $table->string('unit', 30)->nullable();
            $table->decimal('quantity', 15, 3)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);         // = quantity × unit_price
            $table->date('cost_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('project_id');
            $table->index('work_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_costs');
    }
};
