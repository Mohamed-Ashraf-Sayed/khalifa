<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfis', function (Blueprint $table) {
            $table->id();
            $table->string('rfi_number')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->text('question');
            $table->text('answer')->nullable();
            $table->string('status')->default('open'); // open | answered | closed
            $table->string('raised_to')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfis');
    }
};
