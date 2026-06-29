<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * مولّد أرقام المستندات المتسلسلة سنوياً: PREFIX-YYYY-0001
 * يوحّد المنطق المكرّر في 11 كنترولر (بنفس المخرجات بالضبط).
 */
class DocumentNumberGenerator
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function generate(string $modelClass, string $prefix, bool $withTrashed = false): string
    {
        $year = now()->format('Y');

        $query = ($withTrashed && method_exists($modelClass, 'withTrashed'))
            ? $modelClass::withTrashed()
            : $modelClass::query();

        $count = $query->whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $count);
    }
}
