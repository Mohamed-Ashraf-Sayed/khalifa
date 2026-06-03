<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_number', 50);
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 10); // incoming/outgoing
            $table->string('party_name', 150);
            $table->decimal('amount', 15, 2);
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('status', 15)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
