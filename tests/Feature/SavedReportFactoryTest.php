<?php

namespace Tests\Feature;

use App\Analytics\Datasets\DatasetKey;
use App\Models\Employee;
use App\Models\SavedReport;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class SavedReportFactoryTest extends TestCase
{
    public function test_it_builds_a_versioned_semantic_report_definition(): void
    {
        $report = SavedReport::factory()->make([
            'owner_employee_id' => 1,
        ]);

        $this->assertSame(
            DatasetKey::TRANSACTIONS,
            $report->dataset,
        );

        $this->assertSame(1, $report->definition_version);

        $this->assertSame(
            [
                'booking_month',
                'currency',
            ],
            $report->definition['dimensions'],
        );

        $this->assertSame(
            ['total_amount'],
            $report->definition['measures'],
        );

        $this->assertSame(
            'last_30_days',
            $report->definition['relative_date']['preset'],
        );
    }

    public function test_saved_report_defines_ownership_relationships(): void
    {
        $ownerRelationship = (new SavedReport)->owner();

        $this->assertInstanceOf(
            BelongsTo::class,
            $ownerRelationship,
        );

        $this->assertInstanceOf(
            Employee::class,
            $ownerRelationship->getRelated(),
        );

        $this->assertInstanceOf(
            HasMany::class,
            (new Employee)->savedReports(),
        );
    }

    public function test_saved_reports_use_recoverable_deletion(): void
    {
        $this->assertContains(
            SoftDeletes::class,
            class_uses_recursive(SavedReport::class),
        );
    }
}
