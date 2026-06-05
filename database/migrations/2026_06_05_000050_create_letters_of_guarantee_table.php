<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letters_of_guarantee', function (Blueprint $table) {
            $table->id();
            $table->string('lg_number')->unique();
            $table->string('type');                 // bid | performance | advance
            $table->string('beneficiary');
            $table->string('bank_name')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('status')->default('active'); // active | released | expired | cancelled
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letters_of_guarantee');
    }
};
