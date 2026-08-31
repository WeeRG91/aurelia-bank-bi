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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('reversal_of_transaction_id')
                ->nullable()
                ->unique()
                ->constrained('transactions')
                ->restrictOnDelete();
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE transactions
                    ADD CONSTRAINT transactions_cannot_reverse_itself_check
                        CHECK (
                            reversal_of_transaction_id IS NULL
                            OR reversal_of_transaction_id <> id
                        )
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            <<<'SQL'
                ALTER TABLE transactions
                    DROP CONSTRAINT transactions_cannot_reverse_itself_check
            SQL
        );

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['reversal_of_transaction_id']);
            $table->dropUnique(['reversal_of_transaction_id']);
            $table->dropColumn('reversal_of_transaction_id');
        });
    }
};
