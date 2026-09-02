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
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('dataset', 50);
            $table->unsignedSmallInteger('definition_version')->default(1);
            $table->jsonb('definition');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_employee_id', 'updated_at']);
            $table->index(['dataset', 'updated_at']);
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE saved_reports
                    ADD CONSTRAINT saved_reports_name_not_blank_check
                        CHECK (btrim(name) <> ''),
                    ADD CONSTRAINT saved_reports_description_not_blank_check
                        CHECK (
                            description IS NULL
                            OR btrim(description) <> ''
                        ),
                    ADD CONSTRAINT saved_reports_dataset_format_check
                        CHECK (dataset ~ '^[a-z][a-z0-9_]*$'),
                    ADD CONSTRAINT saved_reports_definition_version_positive_check
                        CHECK (definition_version >= 1),
                    ADD CONSTRAINT saved_reports_definition_object_check
                        CHECK (jsonb_typeof(definition) = 'object')
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_reports');
    }
};
