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
        Schema::create('report_exports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('saved_report_id')->constrained('saved_reports')->restrictOnDelete();
            $table->foreignId('requested_by_employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('dataset', 50);
            $table->unsignedSmallInteger('definition_version');
            $table->jsonb('definition');
            $table->string('format', 10);
            $table->string('status', 20)->default('queued');
            $table->string('disk', 50)->nullable();
            $table->string('path', 1024)->nullable();
            $table->string('filename')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestamps();

            $table->index([
                'requested_by_employee_id',
                'status',
                'created_at',
            ]);

            $table->index([
                'status',
                'created_at',
            ]);

            $table->index('expires_at');
        });

        DB::statement(
            <<<'SQL'
            ALTER TABLE report_exports
            ADD CONSTRAINT report_exports_status_check
                CHECK (
                    status IN (
                        'queued',
                        'processing',
                        'completed',
                        'failed',
                        'expired'
                    )
                ),
            ADD CONSTRAINT report_exports_format_check
                CHECK (format IN ('csv', 'xlsx')),
            ADD CONSTRAINT report_exports_row_count_check
                CHECK (row_count IS NULL OR row_count >= 0),
            ADD CONSTRAINT report_exports_file_size_check
                CHECK (
                    file_size_bytes IS NULL
                    OR file_size_bytes >= 0
                ),
            ADD CONSTRAINT report_exports_timestamps_check
                CHECK (
                    finished_at IS NULL
                    OR (
                        started_at IS NOT NULL
                        AND finished_at >= started_at
                    )
                )
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
