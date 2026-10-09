<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'warehouse';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('warehouse')->create(
            'etl_load_runs',
            function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('pipeline', 100);
                $table->string('status', 20)->default('running');
                $table->unsignedInteger('extracted_rows')->default(0);
                $table->unsignedInteger('loaded_rows')->default(0);
                $table->string('failure_code', 100)->nullable();
                $table->timestampTz('started_at');
                $table->timestampTz('finished_at')->nullable();
                $table->timestampsTz();

                $table->index(['pipeline', 'started_at']);

                $table->index(['status', 'started_at']);
            });

        DB::connection('warehouse')->statement(<<<'SQL'
            ALTER TABLE warehouse.etl_load_runs
            ADD CONSTRAINT etl_load_runs_pipeline_format_check
                CHECK (pipeline ~ '^[a-z][a-z0-9_]{2,99}$'),
            ADD CONSTRAINT etl_load_runs_status_check
                CHECK (status IN ('running', 'succeeded', 'failed')),
            ADD CONSTRAINT etl_load_runs_row_counts_check
                CHECK (
                    extracted_rows >= 0
                    AND loaded_rows >= 0
                    AND loaded_rows <= extracted_rows
                ),
            ADD CONSTRAINT etl_load_runs_finished_at_check
                CHECK (
                    finished_at IS NULL
                    OR finished_at >= started_at
                ),
            ADD CONSTRAINT etl_load_runs_failure_code_format_check
                CHECK (
                    failure_code IS NULL
                    OR failure_code ~ '^[a-z][a-z0-9_]{2,99}$'
                ),
            ADD CONSTRAINT etl_load_runs_lifecycle_check
                CHECK (
                    (
                        status = 'running'
                        AND finished_at IS NULL
                        AND failure_code IS NULL
                    )
                    OR (
                        status = 'succeeded'
                        AND finished_at IS NOT NULL
                        AND failure_code IS NULL
                    )
                    OR (
                        status = 'failed'
                        AND finished_at IS NOT NULL
                        AND failure_code IS NOT NULL
                    )
                )
            SQL);

        DB::connection('warehouse')->statement(<<<'SQL'
            CREATE UNIQUE INDEX
                etl_load_runs_one_running_per_pipeline_unique
            ON warehouse.etl_load_runs (pipeline)
            WHERE status = 'running'
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('warehouse')->dropIfExists('etl_load_runs');
    }
};
