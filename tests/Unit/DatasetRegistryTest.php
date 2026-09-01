<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DatasetStatus;
use App\Analytics\Datasets\UnknownDataset;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DatasetRegistryTest extends TestCase
{
    public function test_dataset_keys_have_stable_values(): void
    {
        $this->assertSame(
            [
                'customer_overview',
                'account_balances',
                'transactions',
                'card_activity',
                'loans',
                'loan_repayments',
                'branch_performance',
            ],
            array_map(
                fn (DatasetKey $key): string => $key->value,
                DatasetKey::cases(),
            ),
        );
    }

    public function test_registry_contains_unique_descriptive_definitions(): void
    {
        $definitions = (new DatasetRegistry)->all();

        $keys = array_map(
            fn ($definition): string => $definition->key->value,
            $definitions,
        );

        $this->assertCount(7, $definitions);
        $this->assertCount(7, array_unique($keys));

        foreach ($definitions as $definition) {
            $this->assertNotSame('', trim($definition->label));
            $this->assertNotSame('', trim($definition->description));
            $this->assertNotSame('', trim($definition->grain));
            $this->assertSame(
                DatasetStatus::DRAFT,
                $definition->status,
            );
        }
    }

    public function test_registry_resolves_enum_and_string_identifiers(): void
    {
        $registry = new DatasetRegistry;

        $fromEnum = $registry->get(DatasetKey::TRANSACTIONS);
        $fromString = $registry->get('transactions');

        $this->assertSame($fromEnum, $fromString);
        $this->assertSame('Transactions', $fromEnum->label);
    }

    public function test_unknown_dataset_is_rejected(): void
    {
        $registry = new DatasetRegistry;

        $this->assertNull(
            $registry->find('arbitrary_database_table'),
        );

        $this->expectException(UnknownDataset::class);
        $this->expectExceptionMessage(
            'Unknown analytics dataset [arbitrary_database_table].',
        );

        $registry->get('arbitrary_database_table');
    }

    public function test_no_dataset_is_active_before_query_support_exists(): void
    {
        $this->assertSame(
            [],
            (new DatasetRegistry)->active(),
        );
    }

    public function test_dataset_definitions_are_final_and_immutable(): void
    {
        $reflection = new ReflectionClass(
            DatasetDefinition::class,
        );

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}
