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
        Schema::create('account_holders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('role', 20);
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->unique([
                'account_id',
                'customer_id',
                'started_at',
            ]);

            $table->index(['customer_id', 'ended_at']);
            $table->index(['account_id', 'ended_at']);
        });

        DB::statement(
            <<<'SQL'
                ALTER TABLE account_holders
                    ADD CONSTRAINT account_holders_role_check
                        CHECK (role IN ('primary', 'joint')),
                    ADD CONSTRAINT account_holders_lifecycle_dates_check
                        CHECK (ended_at IS NULL OR ended_at >= started_at)
            SQL
        );

        DB::statement(
            <<<'SQL'
                CREATE UNIQUE INDEX account_holders_one_active_primary_per_account
                    ON account_holders (account_id)
                    WHERE role = 'primary' AND ended_at IS NULL
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_holders');
    }
};
