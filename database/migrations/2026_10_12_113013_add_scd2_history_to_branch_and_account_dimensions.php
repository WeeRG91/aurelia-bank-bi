<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'warehouse';

    private const string HISTORICAL_BASELINE = '2020-01-01 00:00:00+00:00';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('warehouse')->table(
            'dim_branch',
            function (Blueprint $table): void {
                $table->unsignedInteger('version')->nullable();
                $table->timestampTz('valid_from')->nullable();
                $table->timestampTz('valid_to')->nullable();
                $table->boolean('is_current')->nullable();
                $table->char('attribute_hash', 64)->nullable();
            }
        );

        Schema::connection('warehouse')->table(
            'dim_account',
            function (Blueprint $table): void {
                $table->unsignedInteger('version')->nullable();
                $table->timestampTz('valid_from')->nullable();
                $table->timestampTz('valid_to')->nullable();
                $table->boolean('is_current')->nullable();
                $table->char('attribute_hash', 64)->nullable();
            }
        );

        $this->backfillBranches();
        $this->backfillAccounts();

        DB::connection('warehouse')->statement(
            <<<'SQL'
            ALTER TABLE warehouse.dim_branch
                DROP CONSTRAINT dim_branch_source_branch_id_unique,
                DROP CONSTRAINT dim_branch_branch_code_unique,
                ALTER COLUMN version SET NOT NULL,
                ALTER COLUMN valid_from SET NOT NULL,
                ALTER COLUMN is_current SET NOT NULL,
                ALTER COLUMN attribute_hash SET NOT NULL,
                ADD CONSTRAINT dim_branch_version_positive_check
                    CHECK (version >= 1),
                ADD CONSTRAINT dim_branch_validity_check
                    CHECK (
                        valid_to IS NULL
                        OR valid_to > valid_from
                    ),
                ADD CONSTRAINT dim_branch_current_state_check
                    CHECK (
                        (
                            is_current = true
                            AND valid_to IS NULL
                        )
                        OR
                        (
                            is_current = false
                            AND valid_to IS NOT NULL
                        )
                    ),
                ADD CONSTRAINT dim_branch_attribute_hash_check
                    CHECK (
                        attribute_hash ~ '^[a-f0-9]{64}$'
                    )
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            ALTER TABLE warehouse.dim_account
                DROP CONSTRAINT dim_account_source_account_id_unique,
                DROP CONSTRAINT dim_account_account_number_unique,
                ALTER COLUMN version SET NOT NULL,
                ALTER COLUMN valid_from SET NOT NULL,
                ALTER COLUMN is_current SET NOT NULL,
                ALTER COLUMN attribute_hash SET NOT NULL,
                ADD CONSTRAINT dim_account_version_positive_check
                    CHECK (version >= 1),
                ADD CONSTRAINT dim_account_validity_check
                    CHECK (
                        valid_to IS NULL
                        OR valid_to > valid_from
                    ),
                ADD CONSTRAINT dim_account_current_state_check
                    CHECK (
                        (
                            is_current = true
                            AND valid_to IS NULL
                        )
                        OR
                        (
                            is_current = false
                            AND valid_to IS NOT NULL
                        )
                    ),
                ADD CONSTRAINT dim_account_attribute_hash_check
                    CHECK (
                        attribute_hash ~ '^[a-f0-9]{64}$'
                    )
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            CREATE UNIQUE INDEX dim_branch_source_version_unique
                ON warehouse.dim_branch (
                    source_branch_id,
                    version
                )
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            CREATE UNIQUE INDEX dim_branch_one_current_source_unique
                ON warehouse.dim_branch (source_branch_id)
                WHERE is_current = true
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            CREATE UNIQUE INDEX dim_branch_one_current_code_unique
                ON warehouse.dim_branch (branch_code)
                WHERE is_current = true
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            CREATE INDEX dim_branch_source_validity_index
                ON warehouse.dim_branch (
                    source_branch_id,
                    valid_from,
                    valid_to
                )
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            CREATE UNIQUE INDEX dim_account_source_version_unique
                ON warehouse.dim_account (
                    source_account_id,
                    version
                )
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            CREATE UNIQUE INDEX dim_account_one_current_source_unique
                ON warehouse.dim_account (source_account_id)
                WHERE is_current = true
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            CREATE UNIQUE INDEX dim_account_one_current_number_unique
                ON warehouse.dim_account (account_number)
                WHERE is_current = true
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            CREATE INDEX dim_account_source_validity_index
                ON warehouse.dim_account (
                    source_account_id,
                    valid_from,
                    valid_to
                )
            SQL,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            $this->hasHistoricalVersions(
                'dim_branch',
                'source_branch_id',
            )
            || $this->hasHistoricalVersions(
                'dim_account',
                'source_account_id',
            )
        ) {
            throw new RuntimeException(
                'Cannot remove SCD2 columns after historical dimension versions exist.',
            );
        }

        DB::connection('warehouse')->statement(
            <<<'SQL'
            DROP INDEX IF EXISTS
                warehouse.dim_branch_source_version_unique,
                warehouse.dim_branch_one_current_source_unique,
                warehouse.dim_branch_one_current_code_unique,
                warehouse.dim_branch_source_validity_index
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            DROP INDEX IF EXISTS
                warehouse.dim_account_source_version_unique,
                warehouse.dim_account_one_current_source_unique,
                warehouse.dim_account_one_current_number_unique,
                warehouse.dim_account_source_validity_index
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            ALTER TABLE warehouse.dim_branch
                DROP CONSTRAINT dim_branch_version_positive_check,
                DROP CONSTRAINT dim_branch_validity_check,
                DROP CONSTRAINT dim_branch_current_state_check,
                DROP CONSTRAINT dim_branch_attribute_hash_check
            SQL,
        );

        DB::connection('warehouse')->statement(
            <<<'SQL'
            ALTER TABLE warehouse.dim_account
                DROP CONSTRAINT dim_account_version_positive_check,
                DROP CONSTRAINT dim_account_validity_check,
                DROP CONSTRAINT dim_account_current_state_check,
                DROP CONSTRAINT dim_account_attribute_hash_check
            SQL,
        );

        Schema::connection('warehouse')->table(
            'dim_branch',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'version',
                    'valid_from',
                    'valid_to',
                    'is_current',
                    'attribute_hash',
                ]);

                $table->unique(
                    'source_branch_id',
                    'dim_branch_source_branch_id_unique',
                );

                $table->unique(
                    'branch_code',
                    'dim_branch_branch_code_unique',
                );
            },
        );

        Schema::connection('warehouse')->table(
            'dim_account',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'version',
                    'valid_from',
                    'valid_to',
                    'is_current',
                    'attribute_hash',
                ]);

                $table->unique(
                    'source_account_id',
                    'dim_account_source_account_id_unique',
                );

                $table->unique(
                    'account_number',
                    'dim_account_account_number_unique',
                );
            },
        );
    }

    private function backfillBranches(): void
    {
        $branches = DB::connection('warehouse')
            ->table('dim_branch')
            ->select([
                'branch_key',
                'branch_code',
                'branch_name',
                'country_code',
                'city',
            ])
            ->orderBy('branch_key')
            ->get();

        foreach ($branches as $branch) {
            DB::connection('warehouse')
                ->table('dim_branch')
                ->where('branch_key', $branch->branch_key)
                ->update([
                    'version' => 1,
                    'valid_from' => self::HISTORICAL_BASELINE,
                    'valid_to' => null,
                    'is_current' => true,
                    'attribute_hash' => $this->attributeHash([
                        'branch_code' => (string) $branch->branch_code,
                        'branch_name' => (string) $branch->branch_name,
                        'country_code' => (string) $branch->country_code,
                        'city' => (string) $branch->city,
                    ]),
                ]);
        }
    }

    private function backfillAccounts(): void
    {
        $accounts = DB::connection('warehouse')
            ->table('dim_account')
            ->select([
                'account_key',
                'account_number',
                'account_type',
                'currency',
                'opened_at',
                'closed_at',
                'status',
            ])
            ->orderBy('account_key')
            ->get();

        foreach ($accounts as $account) {
            DB::connection('warehouse')
                ->table('dim_account')
                ->where('account_key', $account->account_key)
                ->update([
                    'version' => 1,
                    'valid_from' => self::HISTORICAL_BASELINE,
                    'valid_to' => null,
                    'is_current' => true,
                    'attribute_hash' => $this->attributeHash([
                        'account_number' => (string) $account->account_number,
                        'account_type' => (string) $account->account_type,
                        'currency' => (string) $account->currency,
                        'opened_at' => (string) $account->opened_at,
                        'closed_at' => $account->closed_at === null
                            ? null
                            : (string) $account->closed_at,
                        'status' => (string) $account->status,
                    ]),
                ]);
        }
    }

    /**
     * @param  array<string, bool|int|string|null>  $attributes
     */
    private function attributeHash(array $attributes): string
    {
        ksort($attributes, SORT_STRING);

        return hash(
            'sha256',
            json_encode(
                $attributes,
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
            )
        );
    }

    private function hasHistoricalVersions(
        string $table,
        string $sourceColumn,
    ): bool {
        return DB::connection('warehouse')
            ->table($table)
            ->select($sourceColumn)
            ->groupBy($sourceColumn)
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
};
