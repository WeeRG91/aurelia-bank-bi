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
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->cascadeOnDelete();
            $table->foreignId('saved_report_id')->constrained('saved_reports')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('widget_type', 20);
            $table->unsignedTinyInteger('grid_column');
            $table->unsignedInteger('grid_row');
            $table->unsignedTinyInteger('grid_width');
            $table->unsignedTinyInteger('grid_height');
            $table->jsonb('configuration')->nullable();
            $table->timestamps();

            $table->index([
                'dashboard_id',
                'grid_row',
                'grid_column',
                'id',
            ]);

            $table->index('saved_report_id');
        });

        DB::statement(
            <<<'SQL'
            ALTER TABLE dashboard_widgets
            ADD CONSTRAINT dashboard_widgets_title_not_blank_check
                CHECK (btrim(title) <> ''),
            ADD CONSTRAINT dashboard_widgets_type_check
                CHECK (widget_type IN ('kpi', 'chart', 'table')),
            ADD CONSTRAINT dashboard_widgets_grid_column_check
                CHECK (grid_column BETWEEN 0 AND 11),
            ADD CONSTRAINT dashboard_widgets_grid_row_check
                CHECK (grid_row >= 0),
            ADD CONSTRAINT dashboard_widgets_grid_width_check
                CHECK (grid_width BETWEEN 1 AND 12),
            ADD CONSTRAINT dashboard_widgets_grid_height_check
                CHECK (grid_height BETWEEN 1 AND 12),
            ADD CONSTRAINT dashboard_widgets_grid_fit_check
                CHECK (grid_column + grid_width <= 12),
            ADD CONSTRAINT dashboard_widgets_configuration_object_check
                CHECK (
                    configuration IS NULL
                    OR jsonb_typeof(configuration) = 'object'
                )
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
