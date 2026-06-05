<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('log_type')->default('maintenance'); // usage | maintenance
            $table->date('log_date');
            $table->decimal('operating_hours', 10, 2)->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->date('next_service_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_logs');
    }
};
