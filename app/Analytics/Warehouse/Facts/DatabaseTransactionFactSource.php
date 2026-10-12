<?php

namespace App\Analytics\Warehouse\Facts;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class DatabaseTransactionFactSource implements TransactionFactSource
{
    public function __construct(
        private DatabaseManager $database,
    ) {}

    public function rows(): iterable
    {
        $rows = $this->database
            ->connection('warehouse')
            ->table('stg_transactions as staging')
            ->leftJoin(
                'dim_account as account',
                static function (JoinClause $join): void {
                    $join
                        ->on(
                            'account.source_account_id',
                            '=',
                            'staging.source_account_id',
                        )
                        ->whereRaw(
                            <<<'SQL'
                            (
                                (
                                    staging.booked_at IS NOT NULL
                                    AND account.valid_from <= staging.booked_at
                                    AND (
                                        account.valid_to IS NULL
                                        OR staging.booked_at < account.valid_to
                                    )
                                )
                                OR (
                                    staging.booked_at IS NULL
                                    AND account.is_current = true
                                )
                            )
                            SQL,
                        );
                },
            )
            ->leftJoin(
                'dim_branch as branch',
                static function (JoinClause $join): void {
                    $join
                        ->on(
                            'branch.source_branch_id',
                            '=',
                            'staging.source_branch_id',
                        )
                        ->whereRaw(
                            <<<'SQL'
                            (
                                (
                                    staging.booked_at IS NOT NULL
                                    AND branch.valid_from <= staging.booked_at
                                    AND (
                                        branch.valid_to IS NULL
                                        OR staging.booked_at < branch.valid_to
                                    )
                                )
                                OR (
                                    staging.booked_at IS NULL
                                    AND branch.is_current = true
                                )
                            )
                            SQL,
                        );
                },
            )
            ->leftJoin(
                'dim_date as booking_date',
                static function (JoinClause $join): void {
                    $join->on(
                        'booking_date.calendar_date',
                        '=',
                        DB::raw(
                            "(staging.booked_at AT TIME ZONE 'UTC')::date",
                        ),
                    );
                },
            )
            ->leftJoin(
                'dim_date as value_date_dimension',
                'value_date_dimension.calendar_date',
                '=',
                'staging.value_date',
            )
            ->select([
                'staging.source_transaction_id',
                'staging.source_account_id',
                'staging.source_branch_id',
                'staging.transaction_reference',
                'staging.transaction_type',
                'staging.category',
                'staging.amount',
                'staging.transaction_currency',
                'staging.direction',
                'staging.booked_at',
                'staging.value_date',
                'staging.transaction_status',
                'staging.source_updated_at',
                'account.account_key',
                'branch.branch_key',
                'booking_date.date_key as booking_date_key',
                'value_date_dimension.date_key as value_date_key',
            ])
            ->orderBy('staging.source_transaction_id')
            ->cursor();

        foreach ($rows as $row) {
            if ($row->account_key === null) {
                throw new RuntimeException(
                    "Account dimension key is missing for source account [{$row->source_account_id}].",
                );
            }

            if ($row->branch_key === null) {
                throw new RuntimeException(
                    "Branch dimension key is missing for source branch [{$row->source_branch_id}].",
                );
            }

            if (
                $row->booked_at !== null
                && $row->booking_date_key === null
            ) {
                throw new RuntimeException(
                    "Booking date dimension key is missing for transaction [{$row->source_transaction_id}].",
                );
            }

            if (
                $row->value_date !== null
                && $row->value_date_key === null
            ) {
                throw new RuntimeException(
                    "Value date dimension key is missing for transaction [{$row->source_transaction_id}].",
                );
            }

            yield [
                'source_transaction_id' => (int) $row->source_transaction_id,
                'account_key' => (int) $row->account_key,
                'branch_key' => (int) $row->branch_key,
                'booking_date_key' => $row->booking_date_key === null
                    ? null
                    : (int) $row->booking_date_key,
                'value_date_key' => $row->value_date_key === null
                    ? null
                    : (int) $row->value_date_key,
                'transaction_reference' => (string) $row->transaction_reference,
                'transaction_type' => (string) $row->transaction_type,
                'category' => (string) $row->category,
                'amount' => (string) $row->amount,
                'transaction_currency' => (string) $row->transaction_currency,
                'direction' => (string) $row->direction,
                'transaction_status' => (string) $row->transaction_status,
                'source_updated_at' => $row->source_updated_at === null
                    ? null
                    : (string) $row->source_updated_at,
            ];
        }
    }
}
