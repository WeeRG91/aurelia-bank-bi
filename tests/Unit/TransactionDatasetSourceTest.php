<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\UnknownDimension;
use App\Analytics\Queries\Sources\JoinDefinition;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use PHPUnit\Framework\TestCase;

class TransactionDatasetSourceTest extends TestCase
{
    public function test_source_identifies_its_dataset_and_base_table(): void
    {
        $source = new TransactionDatasetSource;

        $this->assertSame(
            DatasetKey::TRANSACTIONS,
            $source->dataset(),
        );

        $this->assertSame(
            'transactions',
            $source->baseTable(),
        );

        $this->assertSame(
            'branches.id',
            $source->branchScopeColumn(),
        );
    }

    public function test_every_semantic_dimension_has_one_physical_mapping(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        $dimensionKeys = array_map(
            fn (DimensionDefinition $dimension): string => $dimension->key,
            $dataset->dimensions(),
        );

        $this->assertSame(
            $dimensionKeys,
            array_keys((new TransactionDatasetSource)->columns()),
        );
    }

    public function test_physical_columns_are_safe_qualified_identifiers(): void
    {
        foreach ((new TransactionDatasetSource)->columns() as $column) {
            $this->assertMatchesRegularExpression(
                '/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
                $column,
            );
        }
    }

    public function test_organizational_dimensions_use_joined_business_columns(): void
    {
        $source = new TransactionDatasetSource;

        $this->assertSame(
            'branches.branch_code',
            $source->column('branch'),
        );

        $this->assertSame(
            'branches.country_code',
            $source->column('country'),
        );

        $this->assertSame(
            'accounts.account_type',
            $source->column('account_type'),
        );
    }

    public function test_source_defines_the_required_join_path(): void
    {
        $joins = array_map(
            fn (JoinDefinition $join): array => [
                $join->table,
                $join->leftColumn,
                $join->rightColumn,
            ],
            (new TransactionDatasetSource)->joins(),
        );

        $this->assertSame(
            [
                [
                    'accounts',
                    'transactions.account_id',
                    'accounts.id',
                ],
                [
                    'branches',
                    'accounts.branch_id',
                    'branches.id',
                ],
            ],
            $joins,
        );
    }

    public function test_unknown_dimension_cannot_become_a_column(): void
    {
        $this->expectException(UnknownDimension::class);
        $this->expectExceptionMessage(
            'Unknown dimension [password] for dataset [transactions].',
        );

        (new TransactionDatasetSource)->column('password');
    }
}
