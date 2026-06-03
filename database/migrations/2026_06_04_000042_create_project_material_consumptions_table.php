<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // سجل تكلفة فقط — لا يمسّ مخزون المواد؛ الجرد الفعلي يُدار في مكان آخر.
        Schema::create('project_material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->date('consumption_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_material_consumptions');
    }
};
