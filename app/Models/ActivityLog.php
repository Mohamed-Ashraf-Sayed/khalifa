<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public const UPDATED_AT = null; // سجل append-only، created_at فقط

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'description', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public const ACTIONS = [
        'created' => 'إضافة',
        'updated' => 'تعديل',
        'deleted' => 'حذف',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * تسجيل حركة على نموذج. يُستدعى مركزياً من AppServiceProvider.
     * يتجاهل عمليات الـconsole (seeders/commands) ونفسه لتفادي التكرار.
     */
    public static function record(string $action, Model $model): void
    {
        if (app()->runningInConsole() || $model instanceof self) {
            return;
        }

        static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => class_basename($model),
            'model_id' => $model->getKey(),
            'description' => class_basename($model).' #'.$model->getKey(),
            'ip_address' => optional(request())->ip(),
            'created_at' => now(),
        ]);
    }
}
