<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw new LogicException(
                'The analytics warehouse requires PostgreSQL.',
            );
        }

        DB::statement(
            'CREATE SCHEMA IF NOT EXISTS warehouse',
        );

        DB::statement(
            "COMMENT ON SCHEMA warehouse IS 'Aurelia governed analytics warehouse'",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw new LogicException(
                'The analytics warehouse requires PostgreSQL.',
            );
        }

        DB::statement(
            'DROP SCHEMA IF EXISTS warehouse',
        );
    }
};
