<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('laborer_name')->nullable();
            $table->decimal('hours', 5, 2)->default(0);
            $table->boolean('present')->default(true);
            $table->decimal('wage', 12, 2)->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['project_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_attendances');
    }
};
