<?php

namespace Tests\Unit;

use App\Models\Contractor;
use App\Models\ContractorExtract;
use App\Models\ContractorExtractItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorExtractTest extends TestCase
{
    use RefreshDatabase;

    private function contractor(): Contractor
    {
        return Contractor::create([
            'contractor_code' => 'C-001',
            'name' => 'مقاول اختبار',
            'is_active' => true,
        ]);
    }

    private function extract(Contractor $contractor, array $overrides = []): ContractorExtract
    {
        return ContractorExtract::create(array_merge([
            'extract_number' => 'EX-001',
            'contractor_id' => $contractor->id,
            'extract_date' => '2026-01-01',
            'additions' => '200.00',
            'deductions' => '100.00',
            'retention_percent' => '10.00',
            'status' => 'pending',
        ], $overrides));
    }

    private function item(ContractorExtract $extract, string $totalPrice): ContractorExtractItem
    {
        return ContractorExtractItem::create([
            'contractor_extract_id' => $extract->id,
            'description' => 'بند',
            'unit' => 'م2',
            'quantity' => '1',
            'unit_price' => $totalPrice,
            'total_price' => $totalPrice,
        ]);
    }

    public function test_recompute_totals_sums_items_into_total_amount(): void
    {
        $extract = $this->extract($this->contractor());
        $this->item($extract, '1000.00');
        $this->item($extract, '500.00');

        $extract->recomputeTotals();

        // 1000.00 + 500.00 = 1500.00
        $this->assertSame('1500.00', $extract->fresh()->total_amount);
    }

    public function test_recompute_totals_computes_net_amount(): void
    {
        $extract = $this->extract($this->contractor());
        $this->item($extract, '1000.00');
        $this->item($extract, '500.00');

        $extract->recomputeTotals();

        // (1500 + 200) - 100 = 1600.00
        $this->assertSame('1600.00', $extract->fresh()->net_amount);
    }

    public function test_recompute_totals_computes_retention_amount(): void
    {
        $extract = $this->extract($this->contractor());
        $this->item($extract, '1000.00');
        $this->item($extract, '500.00');

        $extract->recomputeTotals();

        // net 1600 * 10% = 160.00
        $this->assertSame('160.00', $extract->fresh()->retention_amount);
    }

    public function test_recompute_totals_with_no_items_zeroes_total_and_derives_net(): void
    {
        $extract = $this->extract($this->contractor(), [
            'additions' => '50.00',
            'deductions' => '20.00',
            'retention_percent' => '5.00',
        ]);

        $extract->recomputeTotals();

        $fresh = $extract->fresh();
        // no items -> total 0; net = (0 + 50) - 20 = 30; retention = 30 * 5% = 1.50
        $this->assertSame('0.00', $fresh->total_amount);
        $this->assertSame('30.00', $fresh->net_amount);
        $this->assertSame('1.50', $fresh->retention_amount);
    }

    public function test_recompute_totals_persists_to_database(): void
    {
        $extract = $this->extract($this->contractor());
        $this->item($extract, '1000.00');
        $this->item($extract, '500.00');

        $extract->recomputeTotals();

        $this->assertDatabaseHas('contractor_extracts', [
            'id' => $extract->id,
            'total_amount' => '1500.00',
            'net_amount' => '1600.00',
            'retention_amount' => '160.00',
        ]);
    }
}
