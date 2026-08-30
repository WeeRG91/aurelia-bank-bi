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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_code', 20)->unique();
            $table->string('name', 150);
            $table->string('country_code', 2);
            $table->string('city', 100);
            $table->date('opened_at');
            $table->timestamps();
        });

        DB::statement(
            "ALTER TABLE branches
                    ADD CONSTRAINT branches_country_code_format_check
                    CHECK (country_code ~ '^[A-Z]{2}$')"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
