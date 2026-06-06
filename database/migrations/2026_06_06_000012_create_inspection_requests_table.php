<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ir_number')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('general');
            $table->string('location')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected | closed
            $table->text('result')->nullable();
            $table->string('inspector')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_requests');
    }
};
