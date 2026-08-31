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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('birth_date');
            $table->string('email', 254)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('nationality', 2);
            $table->string('country_of_residence', 2);
            $table->string('city', 100);
            $table->string('postal_code', 20);
            $table->string('occupation', 150)->nullable();
            $table->decimal('annual_income', 19, 2)->nullable();
            $table->string('annual_income_currency', 3)->nullable();
            $table->string('customer_segment', 30);
            $table->string('risk_level', 10);
            $table->date('joined_at');
            $table->string('status', 20);
            $table->timestamps();
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE customers
                    ADD CONSTRAINT customers_customer_number_format_check
                        CHECK (customer_number ~ '^CUS-[0-9]{8}$'),
                    ADD CONSTRAINT customers_nationality_format_check
                        CHECK (nationality ~ '^[A-Z]{2}$'),
                    ADD CONSTRAINT customers_residence_country_format_check
                        CHECK (country_of_residence ~ '^[A-Z]{2}$'),
                    ADD CONSTRAINT customers_income_currency_format_check
                        CHECK (
                            annual_income_currency IS NULL
                            OR annual_income_currency ~ '^[A-Z]{3}$'
                        ),
                    ADD CONSTRAINT customers_income_non_negative_check
                        CHECK (annual_income IS NULL OR annual_income >= 0),
                    ADD CONSTRAINT customers_income_currency_pair_check
                        CHECK (
                            (annual_income IS NULL AND annual_income_currency IS NULL)
                            OR
                            (annual_income IS NOT NULL AND annual_income_currency IS NOT NULL)
                        ),
                    ADD CONSTRAINT customers_lifecycle_dates_check
                        CHECK (birth_date <= joined_at),
                    ADD CONSTRAINT customers_segment_check
                        CHECK (
                            customer_segment IN (
                                'retail',
                                'premium',
                                'private_banking',
                                'business'
                            )
                        ),
                    ADD CONSTRAINT customers_risk_level_check
                        CHECK (risk_level IN ('low', 'medium', 'high')),
                    ADD CONSTRAINT customers_status_check
                        CHECK (status IN ('active', 'inactive', 'suspended', 'closed'))
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
