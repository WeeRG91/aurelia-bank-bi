<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->foreignId('scheduled_report_id')
                ->nullable()
                ->constrained('scheduled_reports')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->dropForeign([
                'scheduled_report_id',
            ]);

            $table->dropColumn('scheduled_report_id');
        });
    }
};
