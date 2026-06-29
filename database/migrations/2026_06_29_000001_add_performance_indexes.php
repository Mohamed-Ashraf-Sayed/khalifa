<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فهارس أداء على الأعمدة كثيرة الفلترة (تقارير + تنبيهات + ترحيل محاسبي).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index('status', 'journal_entries_status_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['is_credit', 'payment_status', 'due_date'], 'expenses_credit_status_due_idx');
        });

        Schema::table('revenues', function (Blueprint $table) {
            $table->index(['payment_status', 'due_date'], 'revenues_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', fn (Blueprint $t) => $t->dropIndex('journal_entries_status_idx'));
        Schema::table('expenses', fn (Blueprint $t) => $t->dropIndex('expenses_credit_status_due_idx'));
        Schema::table('revenues', fn (Blueprint $t) => $t->dropIndex('revenues_status_due_idx'));
    }
};
