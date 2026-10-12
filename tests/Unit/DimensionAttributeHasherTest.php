<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\DimensionAttributeHasher;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DimensionAttributeHasherTest extends TestCase
{
    public function test_equivalent_attributes_have_the_same_hash(): void
    {
        $hasher = new DimensionAttributeHasher;

        $first = $hasher->hash([
            'branch_code' => 'LUX-CENTRAL',
            'branch_name' => 'Luxembourg Central Branch',
            'country_code' => 'LU',
            'city' => 'Luxembourg',
        ]);

        $second = $hasher->hash([
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'branch_name' => 'Luxembourg Central Branch',
            'branch_code' => 'LUX-CENTRAL',
        ]);

        $this->assertSame($first, $second);

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $first,
        );
    }

    public function test_changed_attribute_produces_a_different_hash(): void
    {
        $hasher = new DimensionAttributeHasher;

        $original = $hasher->hash([
            'branch_code' => 'LUX-CENTRAL',
            'branch_name' => 'Luxembourg Central Branch',
            'country_code' => 'LU',
            'city' => 'Luxembourg',
        ]);

        $changed = $hasher->hash([
            'branch_code' => 'LUX-CENTRAL',
            'branch_name' => 'Luxembourg Regional Branch',
            'country_code' => 'LU',
            'city' => 'Luxembourg',
        ]);

        $this->assertNotSame($original, $changed);
    }

    public function test_null_and_empty_string_are_distinct_values(): void
    {
        $hasher = new DimensionAttributeHasher;

        $this->assertNotSame(
            $hasher->hash([
                'closed_at' => null,
            ]),
            $hasher->hash([
                'closed_at' => '',
            ]),
        );
    }

    public function test_empty_attribute_set_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (new DimensionAttributeHasher)->hash([]);
    }

    public function test_nested_attribute_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (new DimensionAttributeHasher)->hash([
            'branch' => [
                'name' => 'Luxembourg Central Branch',
            ],
        ]);
    }
}
