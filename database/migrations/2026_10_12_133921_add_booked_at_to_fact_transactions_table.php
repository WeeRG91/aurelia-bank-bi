<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'warehouse';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('warehouse')->table(
            'fact_transactions',
            function (Blueprint $table): void {
                $table->timestampTz('booked_at')->nullable();
            }
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            UPDATE warehouse.fact_transactions AS fact
            SET booked_at = staging.booked_at
            FROM warehouse.stg_transactions AS staging
            WHERE staging.source_transaction_id =
                fact.source_transaction_id
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            ALTER TABLE warehouse.fact_transactions
                DROP CONSTRAINT
                    fact_transactions_booking_state_check,
                ADD CONSTRAINT
                    fact_transactions_booking_state_check
                    CHECK (
                        (
                            transaction_status IN ('booked', 'reversed')
                            AND booked_at IS NOT NULL
                            AND booking_date_key IS NOT NULL
                            AND value_date_key IS NOT NULL
                        )
                        OR
                        (
                            transaction_status IN ('pending', 'failed')
                            AND booked_at IS NULL
                            AND booking_date_key IS NULL
                            AND value_date_key IS NULL
                        )
                    ),
                ADD CONSTRAINT
                    fact_transactions_booking_date_matches_timestamp_check
                    CHECK (
                        booked_at IS NULL
                        OR booking_date_key = to_char(
                            booked_at AT TIME ZONE 'UTC',
                            'YYYYMMDD'
                        )::integer
                    )
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('warehouse')->statement(
            <<<'SQL'
            ALTER TABLE warehouse.fact_transactions
                DROP CONSTRAINT
                    fact_transactions_booking_date_matches_timestamp_check,
                DROP CONSTRAINT
                    fact_transactions_booking_state_check,
                ADD CONSTRAINT
                    fact_transactions_booking_state_check
                    CHECK (
                        (
                            transaction_status IN ('booked', 'reversed')
                            AND booking_date_key IS NOT NULL
                            AND value_date_key IS NOT NULL
                        )
                        OR
                        (
                            transaction_status IN ('pending', 'failed')
                            AND booking_date_key IS NULL
                            AND value_date_key IS NULL
                        )
                    )
            SQL,
        );

        Schema::connection('warehouse')->table(
            'fact_transactions',
            function (Blueprint $table): void {
                $table->dropColumn('booked_at');
            }
        );
    }
};
