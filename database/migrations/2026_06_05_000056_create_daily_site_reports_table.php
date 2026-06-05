<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_site_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->string('weather')->nullable();
            $table->text('work_done')->nullable();
            $table->unsignedInteger('labor_count')->default(0);
            $table->text('equipment_notes')->nullable();
            $table->text('progress_notes')->nullable();
            $table->text('incidents')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_site_reports');
    }
};
