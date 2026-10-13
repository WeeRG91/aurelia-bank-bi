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
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_employee_id')
                ->constrained('employees')
                ->restrictOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_employee_id', 'updated_at']);
        });

        DB::statement(
            <<<'SQL'
            ALTER TABLE dashboards
            ADD CONSTRAINT dashboards_name_not_blank_check
                CHECK (btrim(name) <> ''),
            ADD CONSTRAINT dashboards_description_not_blank_check
                CHECK (
                    description IS NULL
                    OR btrim(description) <> ''
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            CREATE UNIQUE INDEX dashboards_one_default_per_employee_unique
            ON dashboards (owner_employee_id)
            WHERE is_default = true
                AND deleted_at IS NULL
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboards');
    }
};
