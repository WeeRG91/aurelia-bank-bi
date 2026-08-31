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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('loan_number', 21)->unique();
            $table->string('loan_type', 20);
            $table->decimal('principal', 19, 2);
            $table->string('currency', 3);
            $table->decimal('interest_rate', 9, 6);
            $table->unsignedSmallInteger('term_months');
            $table->date('start_date');
            $table->date('maturity_date');
            $table->string('status', 20);
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['status', 'maturity_date']);
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE loans
                    ADD CONSTRAINT loans_number_format_check
                        CHECK (
                            loan_number ~ '^LOAN-[A-Z0-9]{16}$'
                        ),
                    ADD CONSTRAINT loans_type_check
                        CHECK (
                            loan_type IN (
                                'personal',
                                'mortgage',
                                'auto',
                                'business'
                            )
                        ),
                    ADD CONSTRAINT loans_principal_positive_check
                        CHECK (principal > 0),
                    ADD CONSTRAINT loans_currency_format_check
                        CHECK (currency ~ '^[A-Z]{3}$'),
                    ADD CONSTRAINT loans_interest_rate_range_check
                        CHECK (
                            interest_rate >= 0
                            AND interest_rate < 100
                        ),
                    ADD CONSTRAINT loans_term_months_range_check
                        CHECK (
                            term_months >= 1
                            AND term_months <= 600
                        ),
                    ADD CONSTRAINT loans_lifecycle_dates_check
                        CHECK (maturity_date > start_date),
                    ADD CONSTRAINT loans_status_check
                        CHECK (
                            status IN (
                                'pending',
                                'active',
                                'paid',
                                'defaulted',
                                'cancelled'
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
        Schema::dropIfExists('loans');
    }
};
