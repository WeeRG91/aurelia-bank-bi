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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_report_id')->constrained('saved_reports')->restrictOnDelete();
            $table->foreignId('created_by_employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('format', 10);
            $table->string('frequency', 20);
            $table->time('run_time', 0);
            $table->string('timezone', 64);
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampTz('next_run_at');
            $table->timestampTz('last_dispatched_at')->nullable();
            $table->timestamps();

            $table->index([
                'status',
                'next_run_at',
            ]);

            $table->index([
                'created_by_employee_id',
                'status',
            ]);
        });

        DB::statement(
            <<<'SQL'
            ALTER TABLE scheduled_reports
            ADD CONSTRAINT scheduled_reports_name_not_blank_check
                CHECK (btrim(name) <> ''),
            ADD CONSTRAINT scheduled_reports_format_check
                CHECK (format IN ('csv', 'xlsx')),
            ADD CONSTRAINT scheduled_reports_frequency_check
                CHECK (
                    frequency IN (
                        'daily',
                        'weekly',
                        'monthly'
                    )
                ),
            ADD CONSTRAINT scheduled_reports_status_check
                CHECK (status IN ('active', 'paused')),
            ADD CONSTRAINT scheduled_reports_timezone_not_blank_check
                CHECK (btrim(timezone) <> ''),
            ADD CONSTRAINT scheduled_reports_recurrence_configuration_check
                CHECK (
                    (
                        frequency = 'daily'
                        AND weekday IS NULL
                        AND day_of_month IS NULL
                    )
                    OR
                    (
                        frequency = 'weekly'
                        AND weekday BETWEEN 1 AND 7
                        AND day_of_month IS NULL
                    )
                    OR
                    (
                        frequency = 'monthly'
                        AND weekday IS NULL
                        AND day_of_month BETWEEN 1 AND 28
                    )
                ),
            ADD CONSTRAINT scheduled_reports_dispatch_sequence_check
                CHECK (
                    last_dispatched_at IS NULL
                    OR next_run_at > last_dispatched_at
                )
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
