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
        Schema::create('analytics_audit_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('actor_employee_id')
                ->nullable()
                ->constrained('employees')
                ->restrictOnDelete();
            $table->string('action', 50);
            $table->string('outcome', 20);
            $table->string('source', 20);
            $table->string('dataset', 50)->nullable();
            $table->string('subject_type', 100)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->jsonb('context');
            $table->timestampTz('occurred_at');

            $table->index([
                'actor_employee_id',
                'occurred_at',
            ]);

            $table->index([
                'action',
                'outcome',
                'occurred_at',
            ]);

            $table->index([
                'dataset',
                'occurred_at',
            ]);

            $table->index([
                'subject_type',
                'subject_id',
                'occurred_at',
            ]);

            $table->index('request_id');
        });

        DB::statement(
            <<<'SQL'
            ALTER TABLE analytics_audit_events
                ADD CONSTRAINT analytics_audit_events_action_format_check
                    CHECK (action ~ '^[a-z][a-z0-9_]{2,49}$'),
                ADD CONSTRAINT analytics_audit_events_outcome_check
                    CHECK (outcome IN ('succeeded', 'denied', 'failed')),
                ADD CONSTRAINT analytics_audit_events_source_check
                    CHECK (source IN ('web', 'queue', 'scheduler', 'system')),
                ADD CONSTRAINT analytics_audit_events_dataset_format_check
                    CHECK (
                        dataset IS NULL
                        OR dataset ~ '^[a-z][a-z0-9_]{2,49}$'
                    ),
                ADD CONSTRAINT analytics_audit_events_subject_pair_check
                    CHECK (
                        (subject_type IS NULL AND subject_id IS NULL)
                        OR
                        (subject_type IS NOT NULL AND subject_id IS NOT NULL)
                    ),
                ADD CONSTRAINT analytics_audit_events_context_object_check
                    CHECK (jsonb_typeof(context) = 'object'),
                ADD CONSTRAINT analytics_audit_events_actor_source_check
                    CHECK (
                        source = 'system'
                        OR actor_employee_id IS NOT NULL
                    )
            SQL
        );

        DB::unprepared(
            <<<'SQL'
            CREATE FUNCTION prevent_analytics_audit_event_mutation()
            RETURNS trigger
            AS $$
            BEGIN
                RAISE EXCEPTION
                    'Analytics audit events are append-only and cannot be modified or deleted.';
            END;
            $$
            LANGUAGE plpgsql
            SQL
        );

        DB::unprepared(
            <<<'SQL'
            CREATE TRIGGER analytics_audit_events_prevent_mutation
            BEFORE UPDATE OR DELETE
            ON analytics_audit_events
            FOR EACH ROW
            EXECUTE FUNCTION prevent_analytics_audit_event_mutation()
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_audit_events');

        DB::unprepared(
            'DROP FUNCTION IF EXISTS prevent_analytics_audit_event_mutation()',
        );
    }
};
