<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\EtlPipelineResult;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EtlPipelineResultTest extends TestCase
{
    public function test_it_preserves_valid_row_counts(): void
    {
        $result = new EtlPipelineResult(
            extractedRows: 125,
            loadedRows: 120,
        );

        $this->assertSame(125, $result->extractedRows);
        $this->assertSame(120, $result->loadedRows);
    }

    public function test_negative_counts_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EtlPipelineResult(
            extractedRows: -1,
            loadedRows: 0,
        );
    }

    public function test_loaded_rows_cannot_exceed_extracted_rows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EtlPipelineResult(
            extractedRows: 10,
            loadedRows: 11,
        );
    }
}
