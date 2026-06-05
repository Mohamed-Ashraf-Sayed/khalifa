<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number')->unique();
            $table->string('type');
            $table->string('provider');
            $table->decimal('coverage_amount', 15, 2);
            $table->decimal('premium', 15, 2)->nullable();
            $table->date('start_date');
            $table->date('expiry_date');
            $table->string('status')->default('active');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
