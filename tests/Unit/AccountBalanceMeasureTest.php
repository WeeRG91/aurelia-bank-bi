<?php

namespace Tests\Unit;

use App\Analytics\Datasets\AggregationFunction;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\MeasureDefinition;
use App\Analytics\Queries\Sources\AccountBalanceDatasetSource;
use PHPUnit\Framework\TestCase;

class AccountBalanceMeasureTest extends TestCase
{
    public function test_account_balance_measures_have_stable_keys(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::ACCOUNT_BALANCES);

        $this->assertSame(
            [
                'snapshot_count',
                'total_ledger_balance',
                'total_available_balance',
                'average_ledger_balance',
                'average_available_balance',
            ],
            array_map(
                fn (MeasureDefinition $measure): string => $measure->key,
                $dataset->measures(),
            ),
        );
    }

    public function test_balance_measures_require_currency_and_snapshot_context(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::ACCOUNT_BALANCES);

        foreach ([
            'total_ledger_balance',
            'total_available_balance',
            'average_ledger_balance',
            'average_available_balance',
        ] as $measureKey) {
            $measure = $dataset->measure($measureKey);

            $this->assertSame(
                ['currency', 'snapshot_date'],
                $measure->requiredContextDimensions(),
            );
        }

        $this->assertSame(
            AggregationFunction::AVERAGE,
            $dataset->measure('average_ledger_balance')->aggregation,
        );
    }

    public function test_physical_source_covers_every_dimension_and_measure(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::ACCOUNT_BALANCES);

        $source = new AccountBalanceDatasetSource;

        $this->assertSame(
            array_map(
                fn ($dimension): string => $dimension->key,
                $dataset->dimensions(),
            ),
            array_keys($source->columns()),
        );

        $this->assertSame(
            array_map(
                fn ($measure): string => $measure->key,
                $dataset->measures(),
            ),
            array_keys($source->measureSources()),
        );
    }

    public function test_balance_source_uses_snapshot_fact_columns(): void
    {
        $source = new AccountBalanceDatasetSource;

        $this->assertSame(
            'account_balance_snapshots.ledger_balance',
            $source->measureSource('total_ledger_balance')->column,
        );

        $this->assertSame(
            'account_balance_snapshots.available_balance',
            $source->measureSource('total_available_balance')->column,
        );
    }
}
