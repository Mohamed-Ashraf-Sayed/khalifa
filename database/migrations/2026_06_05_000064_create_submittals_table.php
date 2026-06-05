<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submittals', function (Blueprint $table) {
            $table->id();
            $table->string('submittal_number')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('material'); // material | shop_drawing | method_statement | sample | other
            $table->string('spec_section')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('submitted'); // submitted | under_review | approved | approved_as_noted | rejected
            $table->string('submitted_to')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submittals');
    }
};
