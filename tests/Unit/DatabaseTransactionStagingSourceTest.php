<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\DatabaseTransactionStagingSource;
use Tests\TestCase;

final class DatabaseTransactionStagingSourceTest extends TestCase
{
    public function test_query_uses_operational_transaction_relationships(): void
    {
        $source = app(
            DatabaseTransactionStagingSource::class,
        );

        $sql = strtolower(
            $source->query()->toSql(),
        );

        $this->assertStringContainsString(
            'from "transactions"',
            $sql,
        );
        $this->assertStringContainsString(
            'inner join "accounts"',
            $sql,
        );
        $this->assertStringContainsString(
            'inner join "branches"',
            $sql,
        );
        $this->assertStringContainsString(
            '"transactions"."id" as "source_transaction_id"',
            $sql,
        );
        $this->assertStringContainsString(
            '"accounts"."id" as "source_account_id"',
            $sql,
        );
        $this->assertStringContainsString(
            '"branches"."id" as "source_branch_id"',
            $sql,
        );
        $this->assertStringContainsString(
            'greatest(',
            $sql,
        );
        $this->assertStringContainsString(
            'order by "transactions"."id" asc',
            $sql,
        );
    }

    public function test_query_requires_no_external_values(): void
    {
        $source = app(
            DatabaseTransactionStagingSource::class,
        );

        $this->assertSame(
            [],
            $source->query()->getBindings(),
        );
    }
}
