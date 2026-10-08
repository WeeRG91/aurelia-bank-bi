<?php

namespace Tests\Unit;

use Tests\TestCase;

final class WarehouseConnectionConfigurationTest extends TestCase
{
    public function test_warehouse_uses_a_dedicated_postgresql_schema(): void
    {
        $operational = config(
            'database.connections.pgsql',
        );

        $warehouse = config(
            'database.connections.warehouse',
        );

        $this->assertIsArray($operational);
        $this->assertIsArray($warehouse);

        $this->assertSame(
            'pgsql',
            $warehouse['driver'],
        );

        $this->assertSame(
            'public',
            $operational['search_path'],
        );

        $this->assertSame(
            'warehouse',
            $warehouse['search_path'],
        );

        $this->assertNotSame(
            $operational['search_path'],
            $warehouse['search_path'],
        );
    }

    public function test_local_warehouse_defaults_to_operational_server_settings(): void
    {
        $operational = config(
            'database.connections.pgsql',
        );

        $warehouse = config(
            'database.connections.warehouse',
        );

        $this->assertSame(
            $operational['host'],
            $warehouse['host'],
        );

        $this->assertSame(
            $operational['port'],
            $warehouse['port'],
        );

        $this->assertSame(
            $operational['database'],
            $warehouse['database'],
        );
    }
}
