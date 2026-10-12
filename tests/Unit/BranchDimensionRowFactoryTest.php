<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\BranchDimensionRowFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BranchDimensionRowFactoryTest extends TestCase
{
    public function test_staged_branch_is_mapped_to_dimension_row(): void
    {
        $row = (new BranchDimensionRowFactory)->create(
            [
                'source_branch_id' => 42,
                'branch_code' => 'LUX-CENTRAL',
                'branch_name' => 'Luxembourg Central Branch',
                'branch_country_code' => 'LU',
                'branch_city' => 'Luxembourg',
            ],
            new DateTimeImmutable(
                '2026-10-09T14:30:00+02:00',
            ),
        );

        $this->assertSame(
            [
                'source_branch_id' => 42,
                'branch_code' => 'LUX-CENTRAL',
                'branch_name' => 'Luxembourg Central Branch',
                'country_code' => 'LU',
                'city' => 'Luxembourg',
                'source_updated_at' => null,
                'loaded_at' => '2026-10-09 12:30:00+00:00',
            ],
            $row,
        );
    }
}
