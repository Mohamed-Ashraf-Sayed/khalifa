<?php

namespace Tests\Unit;

use App\Models\Tender;
use App\Services\DocumentNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_number_for_the_year(): void
    {
        $year = now()->format('Y');
        $this->assertSame("TND-{$year}-0001", app(DocumentNumberGenerator::class)->generate(Tender::class, 'TND'));
    }

    public function test_increments_by_existing_count(): void
    {
        $year = now()->format('Y');
        $uid = \App\Models\User::factory()->create()->id;
        Tender::create(['tender_number' => 'X1', 'title' => 'أ', 'status' => 'draft', 'created_by' => $uid]);
        Tender::create(['tender_number' => 'X2', 'title' => 'ب', 'status' => 'draft', 'created_by' => $uid]);

        $this->assertSame("TND-{$year}-0003", app(DocumentNumberGenerator::class)->generate(Tender::class, 'TND'));
    }

    public function test_format_matches_legacy_sprintf(): void
    {
        $year = now()->format('Y');
        $count = Tender::whereYear('created_at', $year)->count() + 1;
        $legacy = sprintf('TND-%s-%04d', $year, $count);

        $this->assertSame($legacy, app(DocumentNumberGenerator::class)->generate(Tender::class, 'TND'));
    }
}
