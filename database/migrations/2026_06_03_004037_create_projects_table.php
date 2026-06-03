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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('project_type', 30)->default('other'); // building/road/bridge/residential/commercial/other
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->decimal('contract_value', 15, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('status', 20)->default('pending'); // pending/in_progress/completed/on_hold/cancelled
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
