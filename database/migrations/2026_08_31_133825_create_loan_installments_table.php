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
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->restrictOnDelete();
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->decimal('principal_due', 19, 2);
            $table->decimal('interest_due', 19, 2);
            $table->decimal('amount_paid', 19, 2)->default(0);
            $table->timestampTz('paid_at')->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->unique(['loan_id', 'installment_number']);
            $table->unique(['loan_id', 'due_date']);
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE loan_installments
                    ADD CONSTRAINT loan_installments_number_positive_check
                        CHECK (installment_number >= 1),
                    ADD CONSTRAINT loan_installments_amounts_non_negative_check
                        CHECK (
                            principal_due >= 0
                            AND interest_due >= 0
                            AND amount_paid >= 0
                        ),
                    ADD CONSTRAINT loan_installments_scheduled_amount_positive_check
                        CHECK (
                            principal_due + interest_due > 0
                        ),
                    ADD CONSTRAINT loan_installments_payment_not_excessive_check
                        CHECK (
                            amount_paid <= principal_due + interest_due
                        ),
                    ADD CONSTRAINT loan_installments_status_check
                        CHECK (
                            status IN (
                                'pending',
                                'partially_paid',
                                'paid',
                                'overdue'
                            )
                        ),
                    ADD CONSTRAINT loan_installments_payment_status_check
                        CHECK (
                            (
                                status IN ('pending', 'overdue')
                                AND amount_paid = 0
                                AND paid_at IS NULL
                            )
                            OR
                            (
                                status = 'partially_paid'
                                AND amount_paid > 0
                                AND amount_paid < principal_due + interest_due
                                AND paid_at IS NULL
                            )
                            OR
                            (
                                status = 'paid'
                                AND amount_paid = principal_due + interest_due
                                AND paid_at IS NOT NULL
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
        Schema::dropIfExists('loan_installments');
    }
};
