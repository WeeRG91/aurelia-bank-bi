<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Queries\Sources\DatasetSourceRegistry;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use LogicException;
use PHPUnit\Framework\TestCase;

class DatasetSourceRegistryTest extends TestCase
{
    public function test_registered_sources_are_resolved_by_enum_and_string(): void
    {
        $registry = new DatasetSourceRegistry;

        $this->assertSame(
            DatasetKey::TRANSACTIONS,
            $registry->get(DatasetKey::TRANSACTIONS)->dataset(),
        );

        $this->assertSame(
            DatasetKey::ACCOUNT_BALANCES,
            $registry->get('account_balances')->dataset(),
        );
    }

    public function test_every_active_dataset_has_a_query_source(): void
    {
        $sources = new DatasetSourceRegistry;

        foreach ((new DatasetRegistry)->active() as $dataset) {
            $this->assertSame(
                $dataset->key,
                $sources->get($dataset->key)->dataset(),
            );
        }
    }

    public function test_unknown_or_unsupported_dataset_is_not_resolved(): void
    {
        $registry = new DatasetSourceRegistry;

        $this->assertNull(
            $registry->find('arbitrary_table'),
        );

        $this->assertNull(
            $registry->find(DatasetKey::LOANS),
        );
    }

    public function test_get_rejects_dataset_without_a_source(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'No query source is registered for dataset [loans].',
        );

        (new DatasetSourceRegistry)->get(DatasetKey::LOANS);
    }

    public function test_duplicate_source_registration_is_rejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Duplicate query source for dataset [transactions].',
        );

        new DatasetSourceRegistry([
            new TransactionDatasetSource,
            new TransactionDatasetSource,
        ]);
    }
}
