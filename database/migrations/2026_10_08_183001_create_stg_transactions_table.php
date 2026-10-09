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
            'stg_transactions',
            function (Blueprint $table) {
                $table->unsignedBigInteger('source_transaction_id')->primary();
                $table->unsignedBigInteger('source_account_id');
                $table->unsignedBigInteger('source_branch_id');

                $table->string('transaction_reference', 24)->unique();
                $table->string('transaction_type', 30);
                $table->string('category', 50);
                $table->decimal('amount', 19, 2);
                $table->string('transaction_currency', 3);
                $table->string('direction', 10);
                $table->timestampTz('booked_at')->nullable();
                $table->date('value_date')->nullable();
                $table->string('transaction_status', 20);

                $table->string('account_number', 32);
                $table->string('account_type', 20);
                $table->string('account_currency', 3);
                $table->date('account_opened_at');
                $table->date('account_closed_at')->nullable();
                $table->string('account_status', 20);

                $table->string('branch_code', 20);
                $table->string('branch_name', 150);
                $table->string('branch_country_code', 2);
                $table->string('branch_city', 100);

                $table->timestamp('source_updated_at')->nullable();
                $table->timestampTz('loaded_at');

                $table->index(['source_branch_id', 'booked_at']);
                $table->index(['transaction_status', 'booked_at']);
                $table->index('source_updated_at');
            });

        DB::connection('warehouse')->statement(
            <<<'SQL'
                ALTER TABLE warehouse.stg_transactions
                    ADD CONSTRAINT stg_transactions_source_ids_positive_check
                        CHECK (
                            source_transaction_id > 0
                            AND source_account_id > 0
                            AND source_branch_id > 0
                        ),
                    ADD CONSTRAINT stg_transactions_reference_format_check
                        CHECK (
                            transaction_reference ~ '^TXN-[A-Z0-9]{20}$'
                        ),
                    ADD CONSTRAINT stg_transactions_type_check
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
                    ADD CONSTRAINT stg_transactions_category_format_check
                        CHECK (category ~ '^[a-z][a-z0-9_]*$'),
                    ADD CONSTRAINT stg_transactions_amount_positive_check
                        CHECK (amount > 0),
                    ADD CONSTRAINT stg_transactions_currency_format_check
                        CHECK (
                            transaction_currency ~ '^[A-Z]{3}$'
                            AND account_currency ~ '^[A-Z]{3}$'
                        ),
                    ADD CONSTRAINT stg_transactions_direction_check
                        CHECK (
                            direction IN ('incoming', 'outgoing')
                        ),
                    ADD CONSTRAINT stg_transactions_status_check
                        CHECK (
                            transaction_status IN (
                                'pending',
                                'booked',
                                'failed',
                                'reversed'
                            )
                        ),
                    ADD CONSTRAINT stg_transactions_booking_state_check
                        CHECK (
                            (
                                transaction_status IN ('booked', 'reversed')
                                AND booked_at IS NOT NULL
                                AND value_date IS NOT NULL
                            )
                            OR
                            (
                                transaction_status IN ('pending', 'failed')
                                AND booked_at IS NULL
                                AND value_date IS NULL
                            )
                        ),
                    ADD CONSTRAINT stg_transactions_account_number_check
                        CHECK (
                            account_number ~ '^AUR-[A-Z]{2}-[0-9]{12}$'
                        ),
                    ADD CONSTRAINT stg_transactions_account_type_check
                        CHECK (
                            account_type IN (
                                'current',
                                'savings',
                                'term_deposit'
                            )
                        ),
                    ADD CONSTRAINT stg_transactions_account_status_check
                        CHECK (
                            account_status IN (
                                'pending',
                                'active',
                                'frozen',
                                'dormant',
                                'closed'
                            )
                        ),
                    ADD CONSTRAINT stg_transactions_account_dates_check
                        CHECK (
                            account_closed_at IS NULL
                            OR account_closed_at >= account_opened_at
                        ),
                    ADD CONSTRAINT stg_transactions_country_code_check
                        CHECK (
                            branch_country_code ~ '^[A-Z]{2}$'
                        ),
                    ADD CONSTRAINT stg_transactions_branch_values_check
                        CHECK (
                            btrim(branch_code) <> ''
                            AND btrim(branch_name) <> ''
                            AND btrim(branch_city) <> ''
                        )
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
                COMMENT ON TABLE warehouse.stg_transactions IS
                    'Current operational transaction records staged for governed warehouse transformations'
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('warehouse')->dropIfExists('stg_transactions');
    }
};
