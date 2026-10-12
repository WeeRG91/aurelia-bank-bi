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
        Schema::connection('warehouse')->create(
            'fact_transactions',
            function (Blueprint $table): void {
                $table->bigIncrements('transaction_fact_key');
                $table->unsignedBigInteger('source_transaction_id')->unique();

                $table->unsignedBigInteger('account_key');
                $table->unsignedBigInteger('branch_key');

                $table->integer('booking_date_key')->nullable();
                $table->integer('value_date_key')->nullable();
                $table->string('transaction_reference', 24)->unique();

                $table->string('transaction_type', 30);
                $table->string('category', 50);
                $table->string('currency', 3);
                $table->string('direction', 10);
                $table->string('transaction_status', 20);

                $table->decimal('absolute_amount', 19, 2);
                $table->decimal('signed_amount', 19, 2);

                $table->timestamp('source_updated_at')->nullable();
                $table->timestampTz('loaded_at');

                $table->foreign('account_key')
                    ->references('account_key')
                    ->on('dim_account')
                    ->restrictOnDelete();

                $table->foreign('branch_key')
                    ->references('branch_key')
                    ->on('dim_branch')
                    ->restrictOnDelete();

                $table->foreign('booking_date_key')
                    ->references('date_key')
                    ->on('dim_date')
                    ->restrictOnDelete();

                $table->foreign('value_date_key')
                    ->references('date_key')
                    ->on('dim_date')
                    ->restrictOnDelete();

                $table->index(['account_key', 'booking_date_key']);
                $table->index(['branch_key', 'booking_date_key']);
                $table->index(['booking_date_key', 'currency']);
                $table->index(['transaction_type', 'booking_date_key']);
                $table->index(['transaction_status', 'booking_date_key']);
            }
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            ALTER TABLE warehouse.fact_transactions
                ADD CONSTRAINT fact_transactions_source_id_positive_check
                    CHECK (source_transaction_id > 0),
                ADD CONSTRAINT fact_transactions_reference_format_check
                    CHECK (
                        transaction_reference ~ '^TXN-[A-Z0-9]{20}$'
                    ),
                ADD CONSTRAINT fact_transactions_type_check
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
                ADD CONSTRAINT fact_transactions_category_format_check
                    CHECK (category ~ '^[a-z][a-z0-9_]*$'),
                ADD CONSTRAINT fact_transactions_currency_format_check
                    CHECK (currency ~ '^[A-Z]{3}$'),
                ADD CONSTRAINT fact_transactions_direction_check
                    CHECK (
                        direction IN ('incoming', 'outgoing')
                    ),
                ADD CONSTRAINT fact_transactions_status_check
                    CHECK (
                        transaction_status IN (
                            'pending',
                            'booked',
                            'failed',
                            'reversed'
                        )
                    ),
                ADD CONSTRAINT fact_transactions_absolute_amount_check
                    CHECK (absolute_amount > 0),
                ADD CONSTRAINT fact_transactions_signed_amount_check
                    CHECK (
                        signed_amount = CASE direction
                            WHEN 'incoming' THEN absolute_amount
                            WHEN 'outgoing' THEN -absolute_amount
                        END
                    ),
                ADD CONSTRAINT fact_transactions_booking_state_check
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

        DB::connection('warehouse')->statement(
            <<<'SQL'
            COMMENT ON TABLE warehouse.fact_transactions IS
                'Transaction fact at one row per operational banking transaction'
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('warehouse')->dropIfExists('fact_transactions');
    }
};
