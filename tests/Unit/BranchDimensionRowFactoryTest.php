<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\BranchDimensionRowFactory;
use App\Analytics\Warehouse\Dimensions\DimensionAttributeHasher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BranchDimensionRowFactoryTest extends TestCase
{
    public function test_staged_branch_is_mapped_to_dimension_row(): void
    {
        $row = (new BranchDimensionRowFactory(
            new DimensionAttributeHasher,
        ))->create(
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
                'attribute_hash' => (new DimensionAttributeHasher)->hash([
                    'branch_code' => 'LUX-CENTRAL',
                    'branch_name' => 'Luxembourg Central Branch',
                    'country_code' => 'LU',
                    'city' => 'Luxembourg',
                ]),
            ],
            $row,
        );
    }

    public function test_only_tracked_attributes_change_the_hash(): void
    {
        $factory = new BranchDimensionRowFactory(
            new DimensionAttributeHasher,
        );

        $source = [
            'source_branch_id' => 42,
            'branch_code' => 'LUX-CENTRAL',
            'branch_name' => 'Luxembourg Central Branch',
            'branch_country_code' => 'LU',
            'branch_city' => 'Luxembourg',
        ];

        $firstLoad = $factory->create(
            $source,
            new DateTimeImmutable('2026-10-12T12:00:00Z'),
        );

        $laterLoad = $factory->create(
            $source,
            new DateTimeImmutable('2026-10-13T12:00:00Z'),
        );

        $changedBranch = $factory->create(
            [
                ...$source,
                'branch_city' => 'Esch-sur-Alzette',
            ],
            new DateTimeImmutable('2026-10-13T12:00:00Z'),
        );

        $this->assertNotSame(
            $firstLoad['loaded_at'],
            $laterLoad['loaded_at'],
        );

        $this->assertSame(
            $firstLoad['attribute_hash'],
            $laterLoad['attribute_hash'],
        );

        $this->assertNotSame(
            $firstLoad['attribute_hash'],
            $changedBranch['attribute_hash'],
        );
    }
}
