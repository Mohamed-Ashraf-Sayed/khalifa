<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class OperationalAlerts extends Notification
{
    /**
     * @param  array<int, array{label:string,count:int,url:string}>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public string $title = 'تنبيهات تشغيلية',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title:string,items:array<int, array{label:string,count:int,url:string}>,total:int}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'items' => $this->items,
            'total' => $this->total,
        ];
    }
}
