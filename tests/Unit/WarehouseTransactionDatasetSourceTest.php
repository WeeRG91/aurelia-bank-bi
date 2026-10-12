<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\MeasureDefinition;
use App\Analytics\Queries\Sources\JoinType;
use App\Analytics\Queries\Sources\MeasureSourceKind;
use App\Analytics\Queries\Sources\WarehouseTransactionDatasetSource;
use PHPUnit\Framework\TestCase;

final class WarehouseTransactionDatasetSourceTest extends TestCase
{
    public function test_source_targets_the_warehouse_star_schema(): void
    {
        $source = new WarehouseTransactionDatasetSource;

        $this->assertSame(
            DatasetKey::TRANSACTIONS,
            $source->dataset(),
        );

        $this->assertSame(
            'warehouse',
            $source->connectionName(),
        );

        $this->assertSame(
            'fact_transactions',
            $source->baseTable(),
        );

        $this->assertSame(
            'dim_branch.source_branch_id',
            $source->branchScopeColumn(),
        );
    }

    public function test_all_semantic_fields_have_physical_mappings(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        $dimensionKeys = array_map(
            static fn (
                DimensionDefinition $dimension,
            ): string => $dimension->key,
            $dataset->dimensions(),
        );

        $measureKeys = array_map(
            static fn (
                MeasureDefinition $measure,
            ): string => $measure->key,
            $dataset->measures(),
        );

        $source = new WarehouseTransactionDatasetSource;

        $this->assertSame(
            $dimensionKeys,
            array_keys($source->dimensionSources()),
        );

        $this->assertSame(
            $measureKeys,
            array_keys($source->measureSources()),
        );
    }

    public function test_value_date_uses_a_nullable_dimension_join(): void
    {
        $joins = (new WarehouseTransactionDatasetSource)
            ->joins();

        $this->assertCount(3, $joins);

        $this->assertSame(
            JoinType::INNER,
            $joins[0]->type,
        );

        $this->assertSame(
            JoinType::INNER,
            $joins[1]->type,
        );

        $this->assertSame(
            JoinType::LEFT,
            $joins[2]->type,
        );

        $this->assertSame(
            'dim_date.calendar_date',
            (new WarehouseTransactionDatasetSource)
                ->dimensionSource('value_date')
                ->column,
        );
    }

    public function test_net_cash_flow_uses_precomputed_signed_amount(): void
    {
        $measure = (new WarehouseTransactionDatasetSource)
            ->measureSource('net_cash_flow');

        $this->assertSame(
            'fact_transactions.signed_amount',
            $measure->column,
        );

        $this->assertSame(
            MeasureSourceKind::COLUMN,
            $measure->kind,
        );

        $this->assertNull($measure->directionColumn);
    }
}
