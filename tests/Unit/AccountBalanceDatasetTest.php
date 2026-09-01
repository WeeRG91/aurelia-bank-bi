<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\DimensionKind;
use App\Analytics\Datasets\Dimensions\BankingDimensions;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Datasets\SensitivityLevel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AccountBalanceDatasetTest extends TestCase
{
    public function test_account_balance_dimensions_have_stable_keys(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::ACCOUNT_BALANCES);

        $this->assertSame(
            [
                'account_number',
                'branch',
                'country',
                'account_type',
                'currency',
                'account_status',
                'snapshot_date',
            ],
            array_map(
                fn (DimensionDefinition $dimension): string => $dimension->key,
                $dataset->dimensions(),
            ),
        );
    }

    public function test_shared_dimensions_are_conformed_across_datasets(): void
    {
        $registry = new DatasetRegistry;

        $transactions = $registry->get(
            DatasetKey::TRANSACTIONS,
        );

        $balances = $registry->get(
            DatasetKey::ACCOUNT_BALANCES,
        );

        foreach ([
            'branch',
            'country',
            'account_type',
        ] as $dimension) {
            $this->assertEquals(
                $transactions->dimension($dimension),
                $balances->dimension($dimension),
            );
        }
    }

    public function test_account_number_is_a_confidential_identifier(): void
    {
        $dimension = (new DatasetRegistry)
            ->get(DatasetKey::ACCOUNT_BALANCES)
            ->dimension('account_number');

        $this->assertSame(
            DimensionKind::IDENTIFIER,
            $dimension->kind,
        );

        $this->assertSame(
            FieldDataType::STRING,
            $dimension->dataType,
        );

        $this->assertSame(
            SensitivityLevel::CONFIDENTIAL,
            $dimension->sensitivity,
        );
    }

    public function test_snapshot_date_is_a_required_temporal_dimension(): void
    {
        $dimension = (new DatasetRegistry)
            ->get(DatasetKey::ACCOUNT_BALANCES)
            ->dimension('snapshot_date');

        $this->assertSame(
            DimensionKind::TEMPORAL,
            $dimension->kind,
        );

        $this->assertSame(
            FieldDataType::DATE,
            $dimension->dataType,
        );

        $this->assertFalse($dimension->nullable);
    }

    public function test_balance_amounts_are_not_dimensions(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::ACCOUNT_BALANCES);

        $this->assertNull(
            $dataset->findDimension('ledger_balance'),
        );

        $this->assertNull(
            $dataset->findDimension('available_balance'),
        );

        $this->assertNull(
            $dataset->findDimension('account_id'),
        );
    }

    public function test_banking_dimension_factory_cannot_be_instantiated_or_extended(): void
    {
        $reflection = new ReflectionClass(
            BankingDimensions::class,
        );

        $this->assertTrue($reflection->isFinal());
        $this->assertFalse($reflection->isInstantiable());
    }
}
