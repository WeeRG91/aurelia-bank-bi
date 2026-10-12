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
        Schema::connection($this->connection)->create(
            'agg_daily_transaction_activity',
            function (Blueprint $table): void {
                $table->string('reporting_timezone', 64);
                $table->integer('reporting_date_key');
                $table->unsignedBigInteger('branch_key');
                $table->string('currency', 3);
                $table->string('transaction_type', 30);

                $table->unsignedBigInteger('transaction_count');
                $table->decimal('total_amount', 24, 2);
                $table->decimal('incoming_amount', 24, 2);
                $table->decimal('outgoing_amount', 24, 2);
                $table->decimal('net_cash_flow', 24, 2);

                $table->timestampTz('refreshed_at', precision: 0);

                $table->primary(
                    [
                        'reporting_timezone',
                        'reporting_date_key',
                        'branch_key',
                        'currency',
                        'transaction_type',
                    ],
                    'agg_daily_transaction_activity_pkey',
                );

                $table->foreign('reporting_date_key')
                    ->references('date_key')
                    ->on('dim_date')
                    ->restrictOnDelete();

                $table->foreign('branch_key')
                    ->references('branch_key')
                    ->on('dim_branch')
                    ->restrictOnDelete();

                $table->index(
                    [
                        'reporting_timezone',
                        'branch_key',
                        'reporting_date_key',
                        'currency',
                    ],
                    'agg_daily_tx_activity_tz_branch_date_currency_index',
                );
            }
        );

        DB::connection($this->connection)->statement(
            <<<'SQL'
            ALTER TABLE warehouse.agg_daily_transaction_activity
                ADD CONSTRAINT agg_daily_tx_activity_timezone_not_blank_check
                    CHECK (btrim(reporting_timezone) <> ''),
                ADD CONSTRAINT agg_daily_tx_activity_currency_format_check
                    CHECK (currency ~ '^[A-Z]{3}$'),
                ADD CONSTRAINT agg_daily_tx_activity_type_check
                    CHECK (
                        transaction_type IN (
                            'transfer',
                            'card_payment',
                            'cash_withdrawal',
                            'cash_deposit',
                            'direct_debit',
                            'fee',
                            'interest',
                            'loan_payment'
                        )
                    ),
                ADD CONSTRAINT agg_daily_tx_activity_count_positive_check
                    CHECK (transaction_count > 0),
                ADD CONSTRAINT agg_daily_tx_activity_amounts_check
                    CHECK (
                        total_amount > 0
                        AND incoming_amount >= 0
                        AND outgoing_amount >= 0
                        AND total_amount = incoming_amount + outgoing_amount
                        AND net_cash_flow = incoming_amount - outgoing_amount
                    )
            SQL,
        );

        DB::connection($this->connection)->statement(
            <<<'SQL'
            COMMENT ON TABLE warehouse.agg_daily_transaction_activity IS
                'Daily transaction activity by timezone, historical branch, currency, and transaction type'
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('agg_daily_transaction_activity');
    }
};
