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
            'dim_date',
            function (Blueprint $table): void {
                $table->unsignedInteger('date_key')->primary();
                $table->date('calendar_date')->unique();

                $table->unsignedSmallInteger('calendar_year');
                $table->unsignedSmallInteger('calendar_quarter');
                $table->unsignedSmallInteger('calendar_month');
                $table->string('month_name', 9);

                $table->unsignedSmallInteger('iso_year');
                $table->unsignedSmallInteger('iso_week');

                $table->unsignedSmallInteger('day_of_month');
                $table->unsignedSmallInteger('iso_weekday');
                $table->boolean('is_weekend');

                $table->index(['calendar_year', 'calendar_quarter']);
                $table->index(['calendar_year', 'calendar_month']);
                $table->index(['iso_year', 'iso_week']);
            }
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
                ALTER TABLE warehouse.dim_date
                    ADD CONSTRAINT dim_date_key_matches_date_check
                        CHECK (
                            date_key =
                                to_char(calendar_date, 'YYYYMMDD')::integer
                        ),
                    ADD CONSTRAINT dim_date_calendar_year_check
                        CHECK (
                            calendar_year =
                                EXTRACT(YEAR FROM calendar_date)::integer
                        ),
                    ADD CONSTRAINT dim_date_calendar_quarter_check
                        CHECK (
                            calendar_quarter =
                                EXTRACT(QUARTER FROM calendar_date)::integer
                        ),
                    ADD CONSTRAINT dim_date_calendar_month_check
                        CHECK (
                            calendar_month =
                                EXTRACT(MONTH FROM calendar_date)::integer
                        ),
                    ADD CONSTRAINT dim_date_iso_year_check
                        CHECK (
                            iso_year =
                                EXTRACT(ISOYEAR FROM calendar_date)::integer
                        ),
                    ADD CONSTRAINT dim_date_iso_week_check
                        CHECK (
                            iso_week =
                                EXTRACT(WEEK FROM calendar_date)::integer
                        ),
                    ADD CONSTRAINT dim_date_day_of_month_check
                        CHECK (
                            day_of_month =
                                EXTRACT(DAY FROM calendar_date)::integer
                        ),
                    ADD CONSTRAINT dim_date_iso_weekday_check
                        CHECK (
                            iso_weekday =
                                EXTRACT(ISODOW FROM calendar_date)::integer
                        ),
                    ADD CONSTRAINT dim_date_weekend_check
                        CHECK (
                            is_weekend = (
                                EXTRACT(ISODOW FROM calendar_date)
                                    IN (6, 7)
                            )
                        ),
                    ADD CONSTRAINT dim_date_month_name_not_blank_check
                        CHECK (btrim(month_name) <> '')
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
                COMMENT ON TABLE warehouse.dim_date IS
                    'Conformed calendar dimension shared by warehouse facts'
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('warehouse')->dropIfExists('dim_date');
    }
};
