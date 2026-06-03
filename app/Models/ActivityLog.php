<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public const UPDATED_AT = null; // سجل append-only، created_at فقط

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'description', 'changes', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'changes' => 'array',
        ];
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
            'changes' => static::captureChanges($action, $model),
            'ip_address' => optional(request())->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * يلتقط الفروق على مستوى الحقل (قديم → جديد) للتدقيق.
     * عند التعديل: الحقول المتغيّرة فقط، كل حقل ['old'=>القيمة السابقة, 'new'=>القيمة الجديدة].
     * عند الإضافة: القيم المُنشأة. يتجاهل أعمدة الطوابع الزمنية.
     */
    private static function captureChanges(string $action, Model $model): ?array
    {
        $ignored = ['created_at', 'updated_at', 'deleted_at'];

        if ($action === 'updated') {
            $changed = array_diff_key($model->getChanges(), array_flip($ignored));
            if (empty($changed)) {
                return null;
            }

            $original = $model->getOriginal();
            $diff = [];
            foreach ($changed as $field => $new) {
                $diff[$field] = [
                    'old' => $original[$field] ?? null,
                    'new' => $new,
                ];
            }

            return $diff;
        }

        if ($action === 'created') {
            $attributes = array_diff_key($model->getAttributes(), array_flip($ignored));

            return empty($attributes) ? null : $attributes;
        }

        return null;
    }
}
