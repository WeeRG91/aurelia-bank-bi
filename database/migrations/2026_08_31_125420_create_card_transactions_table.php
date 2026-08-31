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
        Schema::create('card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('cards')->restrictOnDelete();
            $table->foreignId('account_transaction_id')
                ->nullable()
                ->unique()
                ->constrained('transactions')
                ->restrictOnDelete();
            $table->string('transaction_reference', 24)->unique();
            $table->string('merchant_name', 150);
            $table->char('merchant_category', 4);
            $table->char('merchant_country', 2);
            $table->decimal('amount', 19, 2);
            $table->string('currency', 3);
            $table->string('direction', 10);
            $table->timestampTz('transaction_at');
            $table->string('status', 20);
            $table->timestamps();

            $table->index(['card_id', 'transaction_at']);
            $table->index('transaction_at');
            $table->index(['merchant_category', 'transaction_at']);
            $table->index(['status', 'transaction_at']);
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE card_transactions
                    ADD CONSTRAINT card_transactions_reference_format_check
                        CHECK (
                            transaction_reference ~ '^CTX-[A-Z0-9]{20}$'
                        ),
                    ADD CONSTRAINT card_transactions_merchant_category_format_check
                        CHECK (
                            merchant_category ~ '^[0-9]{4}$'
                        ),
                    ADD CONSTRAINT card_transactions_merchant_country_format_check
                        CHECK (
                            merchant_country ~ '^[A-Z]{2}$'
                        ),
                    ADD CONSTRAINT card_transactions_amount_positive_check
                        CHECK (amount > 0),
                    ADD CONSTRAINT card_transactions_currency_format_check
                        CHECK (currency ~ '^[A-Z]{3}$'),
                    ADD CONSTRAINT card_transactions_direction_check
                        CHECK (direction IN ('incoming', 'outgoing')),
                    ADD CONSTRAINT card_transactions_status_check
                        CHECK (
                            status IN (
                                'pending',
                                'authorized',
                                'declined',
                                'settled',
                                'reversed'
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
        Schema::dropIfExists('card_transactions');
    }
};
