<?php

namespace App\Analytics\Warehouse\Dimensions;

use DateMalformedStringException;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;
use Throwable;

final readonly class DatabaseAccountDimensionWriter implements AccountDimensionWriter
{
    public function __construct(
        private DatabaseManager $database,
    ) {}

    /**
     * @throws Throwable
     */
    public function transaction(callable $callback): mixed
    {
        return $this->database
            ->connection('warehouse')
            ->transaction(
                static fn (): mixed => $callback(),
            );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function synchronize(array $rows): void
    {
        foreach ($rows as $row) {
            $this->synchronizeRow($row);
        }
    }

    /**
     * @param  array<string, int|string|null>  $row
     *
     * @throws DateMalformedStringException
     */
    private function synchronizeRow(array $row): void
    {
        $sourceAccountId = (int) $row['source_account_id'];

        $connection = $this->database->connection('warehouse');

        $connection->select(
            'SELECT pg_advisory_xact_lock(hashtextextended(?, 0))',
            ["dim_account:{$sourceAccountId}"],
        );

        $current = $connection
            ->table('dim_account')
            ->where('source_account_id', $sourceAccountId)
            ->where('is_current', true)
            ->lockForUpdate()
            ->first([
                'account_key',
                'version',
                'valid_from',
                'attribute_hash',
            ]);

        $decision = DimensionVersionDecision::decide(
            currentVersion: $current === null
                ? null
                : (int) $current->version,
            currentHash: $current === null
                ? null
                : (string) $current->attribute_hash,
            incomingHash: (string) $row['attribute_hash'],
        );

        if (
            $decision->action
            === DimensionVersionAction::KEEP_CURRENT
        ) {
            return;
        }

        if (
            $decision->action
            === DimensionVersionAction::CREATE_INITIAL
        ) {
            $this->insertVersion(
                $row,
                $decision->version,
            );

            return;
        }

        $effectiveAt = new DateTimeImmutable(
            (string) $row['loaded_at'],
        );

        $currentValidFrom = new DateTimeImmutable(
            (string) $current->valid_from,
        );

        if ($effectiveAt <= $currentValidFrom) {
            throw new InvalidArgumentException(
                'A successor account version must start after its current version.',
            );
        }

        $connection
            ->table('dim_account')
            ->where('account_key', (int) $current->account_key)
            ->update([
                'valid_to' => $row['loaded_at'],
                'is_current' => false,
            ]);

        $this->insertVersion(
            $row,
            $decision->version,
        );
    }

    /**
     * @param  array<string, int|string|null>  $row
     */
    private function insertVersion(
        array $row,
        int $version,
    ): void {
        $this->database
            ->connection('warehouse')
            ->table('dim_account')
            ->insert([
                ...$row,
                'version' => $version,
                'valid_from' => $row['loaded_at'],
                'valid_to' => null,
                'is_current' => true,
            ]);
    }
}
