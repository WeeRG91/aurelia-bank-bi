<?php

namespace Tests\Unit;

use App\Analytics\Datasets\AggregationFunction;
use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Datasets\DatasetFieldAccess;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DatasetStatus;
use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\DimensionKind;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Datasets\MeasureDefinition;
use App\Analytics\Datasets\SensitivityLevel;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use PHPUnit\Framework\TestCase;

final class DatasetFieldAccessTest extends TestCase
{
    public function test_analysts_can_use_financial_measures_but_not_identifiers(): void
    {
        $access = $this->defaultAccess();

        foreach ([
            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::FINANCE_ANALYST,
        ] as $role) {
            $user = $this->user($role);

            $this->assertFalse(
                $access->canUseDimension(
                    $user,
                    DatasetKey::TRANSACTIONS,
                    'transaction_reference',
                ),
            );

            $this->assertTrue(
                $access->canUseMeasure(
                    $user,
                    DatasetKey::TRANSACTIONS,
                    'total_amount',
                ),
            );
        }
    }

    public function test_approved_roles_can_use_confidential_identifiers(): void
    {
        $access = $this->defaultAccess();

        foreach ([
            EmployeeRole::BRANCH_MANAGER,
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::RISK_ANALYST,
            EmployeeRole::AUDITOR,
        ] as $role) {
            $this->assertTrue(
                $access->canUseDimension(
                    $this->user($role),
                    DatasetKey::TRANSACTIONS,
                    'transaction_reference',
                ),
                "Role {$role->value} should access confidential identifiers.",
            );
        }
    }

    public function test_restricted_fields_require_risk_or_audit_role(): void
    {
        $registry = new DatasetRegistry([
            new DatasetDefinition(
                key: DatasetKey::TRANSACTIONS,
                label: 'Restricted transactions',
                description: 'Dataset used to test restricted fields.',
                grain: 'One row per transaction.',
                status: DatasetStatus::ACTIVE,
                dimensions: [
                    new DimensionDefinition(
                        key: 'restricted_reference',
                        label: 'Restricted Reference',
                        description: 'Highly restricted transaction identifier.',
                        dataType: FieldDataType::STRING,
                        kind: DimensionKind::IDENTIFIER,
                        sensitivity: SensitivityLevel::RESTRICTED,
                        nullable: false,
                    ),
                ],
                measures: [
                    new MeasureDefinition(
                        key: 'restricted_count',
                        label: 'Restricted Count',
                        description: 'Count of highly restricted records.',
                        dataType: FieldDataType::INTEGER,
                        aggregation: AggregationFunction::COUNT,
                        sensitivity: SensitivityLevel::RESTRICTED,
                    ),
                ],
            ),
        ]);

        $access = new DatasetFieldAccess(
            $registry,
            new DatasetAccess($registry),
        );

        foreach ([
            EmployeeRole::RISK_ANALYST,
            EmployeeRole::AUDITOR,
        ] as $role) {
            $user = $this->user($role);

            $this->assertTrue(
                $access->canUseDimension(
                    $user,
                    DatasetKey::TRANSACTIONS,
                    'restricted_reference',
                ),
            );

            $this->assertTrue(
                $access->canUseMeasure(
                    $user,
                    DatasetKey::TRANSACTIONS,
                    'restricted_count',
                ),
            );
        }

        $manager = $this->user(EmployeeRole::BRANCH_MANAGER);

        $this->assertFalse(
            $access->canUseDimension(
                $manager,
                DatasetKey::TRANSACTIONS,
                'restricted_reference',
            ),
        );

        $this->assertFalse(
            $access->canUseMeasure(
                $manager,
                DatasetKey::TRANSACTIONS,
                'restricted_count',
            ),
        );
    }

    public function test_inactive_and_administrator_roles_receive_no_fields(): void
    {
        $access = $this->defaultAccess();

        $this->assertSame(
            [],
            $access->dimensionsFor(
                $this->user(
                    EmployeeRole::AUDITOR,
                    EmployeeStatus::SUSPENDED,
                ),
                DatasetKey::TRANSACTIONS,
            ),
        );

        $this->assertSame(
            [],
            $access->measuresFor(
                $this->user(EmployeeRole::ADMINISTRATOR),
                DatasetKey::TRANSACTIONS,
            ),
        );
    }

    public function test_unknown_fields_are_denied(): void
    {
        $access = $this->defaultAccess();
        $auditor = $this->user(EmployeeRole::AUDITOR);

        $this->assertFalse(
            $access->canUseDimension(
                $auditor,
                DatasetKey::TRANSACTIONS,
                'users_password',
            ),
        );

        $this->assertFalse(
            $access->canUseMeasure(
                $auditor,
                DatasetKey::TRANSACTIONS,
                'password_count',
            ),
        );
    }

    public function test_complete_report_definition_obeys_field_permissions(): void
    {
        $access = $this->defaultAccess();
        $analyst = $this->user(EmployeeRole::BRANCH_ANALYST);

        $this->assertTrue(
            $access->canUseDefinition(
                $analyst,
                DatasetKey::TRANSACTIONS,
                [
                    'dimensions' => [
                        'transaction_type',
                        'currency',
                        'booking_date',
                    ],
                    'measures' => [
                        'transaction_count',
                        'total_amount',
                    ],
                    'filters' => [
                        [
                            'dimension' => 'transaction_type',
                            'operator' => 'equals',
                            'value' => 'transfer',
                        ],
                    ],
                    'relative_date' => [
                        'dimension' => 'booking_date',
                        'preset' => 'last_30_days',
                    ],
                ],
            ),
        );

        $this->assertFalse(
            $access->canUseDefinition(
                $analyst,
                DatasetKey::TRANSACTIONS,
                [
                    'dimensions' => [
                        'transaction_reference',
                    ],
                    'measures' => [],
                    'filters' => [],
                    'relative_date' => null,
                ],
            ),
        );

        $this->assertFalse(
            $access->canUseDefinition(
                $analyst,
                DatasetKey::TRANSACTIONS,
                [
                    'dimensions' => ['currency'],
                    'measures' => ['transaction_count'],
                    'filters' => [
                        [
                            'dimension' => 'transaction_reference',
                            'operator' => 'equals',
                            'value' => 'TXN-EXAMPLE',
                        ],
                    ],
                    'relative_date' => null,
                ],
            ),
        );
    }

    private function defaultAccess(): DatasetFieldAccess
    {
        $registry = new DatasetRegistry;

        return new DatasetFieldAccess(
            $registry,
            new DatasetAccess($registry),
        );
    }

    private function user(
        EmployeeRole $role,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): User {
        $employee = (new Employee)->forceFill([
            'role' => $role,
            'status' => $status,
        ]);

        return (new User)
            ->setRelation('employee', $employee);
    }
}
