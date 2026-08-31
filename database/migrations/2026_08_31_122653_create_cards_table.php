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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('card_reference', 20)->unique();
            $table->char('display_last_four', 4);
            $table->string('card_type', 20);
            $table->date('issued_at');
            $table->date('expires_at');
            $table->string('status', 20);
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['account_id', 'status']);
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE cards
                    ADD CONSTRAINT cards_reference_format_check
                        CHECK (
                            card_reference ~ '^CRD-[A-Z0-9]{16}$'
                        ),
                    ADD CONSTRAINT cards_display_last_four_format_check
                        CHECK (
                            display_last_four ~ '^[0-9]{4}$'
                        ),
                    ADD CONSTRAINT cards_type_check
                        CHECK (
                            card_type IN (
                                'debit',
                                'credit',
                                'prepaid'
                            )
                        ),
                    ADD CONSTRAINT cards_status_check
                        CHECK (
                            status IN (
                                'pending',
                                'active',
                                'blocked',
                                'expired',
                                'cancelled'
                            )
                        ),
                    ADD CONSTRAINT cards_lifecycle_dates_check
                        CHECK (expires_at > issued_at)
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
