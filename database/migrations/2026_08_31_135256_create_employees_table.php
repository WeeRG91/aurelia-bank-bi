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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->string('employee_number', 12)->unique();
            $table->string('department', 30);
            $table->string('job_title', 100);
            $table->string('role', 30);
            $table->date('hired_at');
            $table->date('terminated_at')->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['department', 'status']);
            $table->index(['role', 'status']);
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE employees
                    ADD CONSTRAINT employees_number_format_check
                        CHECK (
                            employee_number ~ '^EMP-[0-9]{8}$'
                        ),
                    ADD CONSTRAINT employees_department_check
                        CHECK (
                            department IN (
                                'branch_operations',
                                'finance',
                                'risk',
                                'audit',
                                'data_analytics',
                                'administration',
                                'management'
                            )
                        ),
                    ADD CONSTRAINT employees_job_title_not_blank_check
                        CHECK (btrim(job_title) <> ''),
                    ADD CONSTRAINT employees_role_check
                        CHECK (
                            role IN (
                                'branch_analyst',
                                'branch_manager',
                                'country_manager',
                                'finance_analyst',
                                'risk_analyst',
                                'auditor',
                                'administrator'
                            )
                        ),
                    ADD CONSTRAINT employees_status_check
                        CHECK (
                            status IN (
                                'active',
                                'inactive',
                                'suspended',
                                'terminated'
                            )
                        ),
                    ADD CONSTRAINT employees_lifecycle_dates_check
                        CHECK (
                            terminated_at IS NULL
                            OR terminated_at >= hired_at
                        ),
                    ADD CONSTRAINT employees_termination_status_check
                        CHECK (
                            (
                                status = 'terminated'
                                AND terminated_at IS NOT NULL
                            )
                            OR
                            (
                                status <> 'terminated'
                                AND terminated_at IS NULL
                            )
                        ),
                    ADD CONSTRAINT employees_branch_role_check
                        CHECK (
                            role NOT IN (
                                'branch_analyst',
                                'branch_manager'
                            )
                            OR branch_id IS NOT NULL
                        )
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
