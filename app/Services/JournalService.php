<?php

namespace App\Services;

use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * خدمة قيود اليومية (القيد المزدوج).
 *
 * المبادئ:
 *  - كل قيد لازم يتكوّن من بندين على الأقل، وكل بند له مدين أو دائن (واحد بس) أكبر من صفر.
 *  - كل العمليات الحسابية عبر bcmath بدقّة 2.
 *  - الإجماليات مشتقّة من البنود (recomputeTotals)، والقيد لازم يكون متوازن قبل أي ترحيل.
 *  - أي كتابة ذرّية داخل DB::transaction.
 */
class JournalService
{
    /**
     * إنشاء قيد يومية بحالة "مسودة" مع بنوده.
     *
     * @param  array  $data   بيانات الرأس (entry_date, description, notes, created_by ...)
     * @param  array  $lines  مصفوفة بنود: account_id, debit, credit, description?, project_id?, cost_center_id?
     * @param  array  $ref    اختياري: source, reference_type, reference_id
     *
     * @throws \InvalidArgumentException إذا قلّت البنود عن 2، أو بند غير صالح، أو القيد غير متوازن.
     */
    public function createEntry(array $data, array $lines, array $ref = []): JournalEntry
    {
        if (count($lines) < 2) {
            throw new \InvalidArgumentException('القيد لازم يحتوي على بندين على الأقل.');
        }

        if (FiscalPeriod::isLocked((string) $data['entry_date'])) {
            throw new \InvalidArgumentException('لا يمكن الترحيل على فترة مالية مقفلة.');
        }

        foreach ($lines as $line) {
            $debit = (string) ($line['debit'] ?? 0);
            $credit = (string) ($line['credit'] ?? 0);

            $hasDebit = bccomp($debit, '0', 2) > 0;
            $hasCredit = bccomp($credit, '0', 2) > 0;

            // لازم بالظبط واحد من (مدين/دائن) أكبر من صفر
            if ($hasDebit === $hasCredit) {
                throw new \InvalidArgumentException('كل بند لازم يكون مدين أو دائن (واحد بس) أكبر من صفر.');
            }
        }

        return DB::transaction(function () use ($data, $lines, $ref) {
            $entry = JournalEntry::create([
                'entry_number' => $this->nextNumber(),
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'source' => $ref['source'] ?? 'manual',
                'reference_type' => $ref['reference_type'] ?? null,
                'reference_id' => $ref['reference_id'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => bccomp((string) ($line['debit'] ?? 0), '0', 2) > 0 ? $line['debit'] : 0,
                    'credit' => bccomp((string) ($line['credit'] ?? 0), '0', 2) > 0 ? $line['credit'] : 0,
                    'description' => $line['description'] ?? null,
                    'project_id' => $line['project_id'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                ]);
            }

            $entry->load('lines');
            $entry->recomputeTotals();

            if (! $entry->isBalanced()) {
                throw new \InvalidArgumentException('القيد غير متوازن: إجمالي المدين لازم يساوي إجمالي الدائن.');
            }

            return $entry;
        });
    }

    /** ترحيل القيد (من مسودة إلى مرحّل) — فقط لو كان متوازناً ومسودة. */
    public function post(JournalEntry $e, ?int $userId = null): JournalEntry
    {
        if ($e->status !== 'draft' || ! $e->isBalanced()) {
            return $e;
        }

        if (FiscalPeriod::isLocked(\Illuminate\Support\Carbon::parse($e->entry_date)->toDateString())) {
            throw new \InvalidArgumentException('لا يمكن ترحيل قيد على فترة مالية مقفلة.');
        }

        $e->update([
            'status' => 'posted',
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);

        return $e;
    }

    /** إلغاء الترحيل (من مرحّل إلى مسودة). */
    public function unpost(JournalEntry $e): JournalEntry
    {
        if ($e->status === 'posted') {
            $e->update([
                'status' => 'draft',
                'posted_by' => null,
                'posted_at' => null,
            ]);
        }

        return $e;
    }

    /** رقم القيد التالي: JV-YYYY-#### بترقيم سنوي. */
    public function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = JournalEntry::whereYear('created_at', $year)->count() + 1;

        return sprintf('JV-%s-%04d', $year, $count);
    }
}
