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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('account_number', 32)->unique();
            $table->string('account_type', 20);
            $table->string('currency', 3);
            $table->date('opened_at');
            $table->date('closed_at')->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE accounts
                    ADD CONSTRAINT accounts_account_number_format_check
                        CHECK (account_number ~ '^AUR-[A-Z]{2}-[0-9]{12}$'),
                    ADD CONSTRAINT accounts_type_check
                        CHECK (
                            account_type IN (
                                'current',
                                'savings',
                                'term_deposit'
                            )
                        ),
                    ADD CONSTRAINT accounts_currency_format_check
                        CHECK (currency ~ '^[A-Z]{3}$'),
                    ADD CONSTRAINT accounts_status_check
                        CHECK (
                            status IN (
                                'pending',
                                'active',
                                'frozen',
                                'dormant',
                                'closed'
                            )
                        ),
                    ADD CONSTRAINT accounts_lifecycle_dates_check
                        CHECK (closed_at IS NULL OR closed_at >= opened_at),
                    ADD CONSTRAINT accounts_closed_status_date_consistency_check
                        CHECK (
                            (status = 'closed' AND closed_at IS NOT NULL)
                            OR
                            (status <> 'closed' AND closed_at IS NULL)
                        )
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
