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
            'dim_account',
            function (Blueprint $table): void {
                $table->bigIncrements('account_key');

                $table->unsignedBigInteger('source_account_id')->unique();
                $table->string('account_number', 32)->unique();

                $table->string('account_type', 20);
                $table->string('currency', 3);
                $table->date('opened_at');
                $table->date('closed_at')->nullable();
                $table->string('status', 20);

                $table->timestamp('source_updated_at')->nullable();
                $table->timestampTz('loaded_at');

                $table->index(['account_type', 'currency']);
                $table->index('status');
            }
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            ALTER TABLE warehouse.dim_account
                ADD CONSTRAINT dim_account_source_id_positive_check
                    CHECK (source_account_id > 0),
                ADD CONSTRAINT dim_account_number_format_check
                    CHECK (
                        account_number ~ '^AUR-[A-Z]{2}-[0-9]{12}$'
                    ),
                ADD CONSTRAINT dim_account_type_check
                    CHECK (
                        account_type IN (
                            'current',
                            'savings',
                            'term_deposit'
                        )
                    ),
                ADD CONSTRAINT dim_account_currency_format_check
                    CHECK (currency ~ '^[A-Z]{3}$'),
                ADD CONSTRAINT dim_account_status_check
                    CHECK (
                        status IN (
                            'pending',
                            'active',
                            'frozen',
                            'dormant',
                            'closed'
                        )
                    ),
                ADD CONSTRAINT dim_account_dates_check
                    CHECK (
                        closed_at IS NULL
                        OR closed_at >= opened_at
                    )
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            COMMENT ON TABLE warehouse.dim_account IS
                'Current governed account dimension sourced from operational accounts'
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('warehouse')->dropIfExists('dim_account');
    }
};
