<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->string('transaction_reference', 24)->unique();
            $table->string('transaction_type', 30);
            $table->string('category', 50);
            $table->decimal('amount', 19, 2);
            $table->string('currency', 3);
            $table->string('direction', 10);
            $table->string('merchant_name', 150)->nullable();
            $table->string('counterparty_account', 64)->nullable();
            $table->timestampTz('booked_at')->nullable();
            $table->date('value_date')->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->index(['account_id', 'booked_at']);
            $table->index('booked_at');
            $table->index(['status', 'booked_at']);
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE transactions
                    ADD CONSTRAINT transactions_reference_format_check
                        CHECK (
                            transaction_reference ~ '^TXN-[A-Z0-9]{20}$'
                        ),
                    ADD CONSTRAINT transactions_type_check
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
                    ADD CONSTRAINT transactions_category_format_check
                        CHECK (category ~ '^[a-z][a-z0-9_]*$'),
                    ADD CONSTRAINT transactions_amount_positive_check
                        CHECK (amount > 0),
                    ADD CONSTRAINT transactions_currency_format_check
                        CHECK (currency ~ '^[A-Z]{3}$'),
                    ADD CONSTRAINT transactions_direction_check
                        CHECK (direction IN ('incoming', 'outgoing')),
                    ADD CONSTRAINT transactions_status_check
                        CHECK (
                            status IN (
                                'pending',
                                'booked',
                                'failed',
                                'reversed'
                            )
                        ),
                    ADD CONSTRAINT transactions_booking_state_check
                        CHECK (
                            (
                                status IN ('booked', 'reversed')
                                AND booked_at IS NOT NULL
                                AND value_date IS NOT NULL
                            )
                            OR
                            (
                                status IN ('pending', 'failed')
                                AND booked_at IS NULL
                                AND value_date IS NULL
                            )
                        )
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
