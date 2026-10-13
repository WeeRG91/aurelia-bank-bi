<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardManager;
use App\Models\Dashboard;
use App\Models\Employee;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DashboardManagerTest extends TestCase
{
    private Employee $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('dashboards', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_employee_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('employees')->insert([
            'id' => 10,
        ]);

        $this->owner = Employee::query()->findOrFail(10);
    }

    public function test_dashboard_can_be_created_without_replacing_default(): void
    {
        $existing = $this->dashboard(
            name: 'Existing default',
            isDefault: true,
        );

        $created = (new DashboardManager)->create(
            $this->owner,
            [
                'name' => 'Liquidity analysis',
                'description' => null,
                'is_default' => false,
            ],
        );

        $this->assertFalse($created->is_default);
        $this->assertTrue(
            $existing->fresh()->is_default,
        );
    }

    public function test_creating_default_replaces_existing_default(): void
    {
        $existing = $this->dashboard(
            name: 'Existing default',
            isDefault: true,
        );

        $created = (new DashboardManager)->create(
            $this->owner,
            [
                'name' => 'New default',
                'description' => 'Treasury overview',
                'is_default' => true,
            ],
        );

        $this->assertFalse(
            $existing->fresh()->is_default,
        );

        $this->assertTrue($created->is_default);

        $this->assertSame(
            1,
            Dashboard::query()
                ->where('owner_employee_id', 10)
                ->where('is_default', true)
                ->count(),
        );
    }

    public function test_updating_dashboard_can_transfer_default_status(): void
    {
        $existing = $this->dashboard(
            name: 'Existing default',
            isDefault: true,
        );

        $replacement = $this->dashboard(
            name: 'Replacement',
            isDefault: false,
        );

        $updated = (new DashboardManager)->update(
            $replacement,
            [
                'name' => 'Replacement default',
                'description' => null,
                'is_default' => true,
            ],
        );

        $this->assertFalse(
            $existing->fresh()->is_default,
        );

        $this->assertTrue($updated->is_default);

        $this->assertSame(
            'Replacement default',
            $updated->name,
        );
    }

    private function dashboard(
        string $name,
        bool $isDefault,
    ): Dashboard {
        /** @var Dashboard $dashboard */
        $dashboard = $this->owner
            ->dashboards()
            ->create([
                'name' => $name,
                'description' => null,
                'is_default' => $isDefault,
            ]);

        return $dashboard;
    }
}
