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
        Schema::connection($this->connection)->create(
            'dim_branch',
            function (Blueprint $table) {
                $table->bigIncrements('branch_key');

                $table->unsignedBigInteger('source_branch_id')->unique();

                $table->string('branch_code', 20)->unique();
                $table->string('branch_name', 150);
                $table->string('country_code', 2);
                $table->string('city', 100);

                $table->timestamp('source_updated_at', precision: 0)->nullable();
                $table->timestampTz('loaded_at', precision: 0);

                $table->index(['country_code', 'city']);
            });

        DB::connection($this->connection)->statement(<<<'SQL'
            ALTER TABLE warehouse.dim_branch
            ADD CONSTRAINT dim_branch_source_id_positive_check
                CHECK (source_branch_id > 0),
            ADD CONSTRAINT dim_branch_code_format_check
                CHECK (
                    branch_code ~ '^[A-Z0-9][A-Z0-9-]*$'
                ),
            ADD CONSTRAINT dim_branch_name_not_blank_check
                CHECK (btrim(branch_name) <> ''),
            ADD CONSTRAINT dim_branch_country_code_check
                CHECK (country_code ~ '^[A-Z]{2}$'),
            ADD CONSTRAINT dim_branch_city_not_blank_check
                CHECK (btrim(city) <> '')
            SQL);

        DB::connection($this->connection)->statement(<<<'SQL'
            COMMENT ON TABLE warehouse.dim_branch IS
            'Current governed branch dimension sourced from operational branches'
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('dim_branch');
    }
};
