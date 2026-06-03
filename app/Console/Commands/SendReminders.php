<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\OperationalAlerts;
use App\Services\AlertService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:send-reminders';

    /**
     * @var string
     */
    protected $description = 'إرسال إشعارات تذكير للمدراء بالتنبيهات التشغيلية المفتوحة';

    public function handle(AlertService $alerts): int
    {
        $total = $alerts->total();

        if ($total <= 0) {
            $this->info('لا توجد تنبيهات مفتوحة — لم يُرسَل أي إشعار.');

            return self::SUCCESS;
        }

        // اختصار عناصر الخدمة إلى {label, count, url} فقط
        $items = array_map(fn (array $a) => [
            'label' => $a['label'],
            'count' => $a['count'],
            'url' => $a['url'],
        ], $alerts->items());

        $users = User::role(['admin', 'manager'])->get();

        foreach ($users as $user) {
            $user->notify(new OperationalAlerts($items, $total));
        }

        $this->info("تم إرسال إشعار التنبيهات إلى {$users->count()} مستخدم.");

        return self::SUCCESS;
    }
}
