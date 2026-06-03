<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('fees', 15, 2)->default(0);
            $table->date('transfer_date');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('transfer_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transfers');
    }
};
