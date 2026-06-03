<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkOverdueInvoices extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:mark-overdue';

    /**
     * @var string
     */
    protected $description = 'تعليم الفواتير المرسلة/المسددة جزئياً المتأخرة عن تاريخ الاستحقاق كـ overdue';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $count = Invoice::whereIn('status', ['sent', 'partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->update(['status' => 'overdue']);

        $this->info("تم تعليم {$count} فاتورة كمتأخرة.");

        return self::SUCCESS;
    }
}
