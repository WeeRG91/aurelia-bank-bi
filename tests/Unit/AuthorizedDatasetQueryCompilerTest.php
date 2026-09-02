<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DatasetStatus;
use App\Analytics\Queries\Authorization\AuthorizedDatasetQueryCompiler;
use App\Analytics\Queries\Authorization\DatasetRowScopeResolver;
use App\Analytics\Queries\Compilation\FilterCompiler;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\DatasetQueryCompiler;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\TestCase;

class AuthorizedDatasetQueryCompilerTest extends TestCase
{
    public function test_branch_analyst_receives_mandatory_branch_scope(): void
    {
        $compiler = $this->compiler($this->transactionRegistry(DatasetStatus::ACTIVE));

        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['transaction_reference', 'currency'],
        );

        $compiled = $compiler->compileFor(
            $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: 42,
            ),
            new TransactionDatasetSource,
            $query,
        );

        $this->assertStringContainsString(
            'WHERE branches.id = ?',
            $compiled->sql,
        );

        $this->assertSame([42, 100], $compiled->bindings);
    }

    public function test_global_analytical_role_has_no_branch_predicate(): void
    {
        $compiled = $this->compiler($this->transactionRegistry(DatasetStatus::ACTIVE))
            ->compileFor(
                $this->user(EmployeeRole::AUDITOR),
                new TransactionDatasetSource,
                new DatasetQuery(
                    dataset: DatasetKey::TRANSACTIONS,
                    dimensions: ['transaction_reference'],
                ),
            );

        $this->assertStringNotContainsString(
            'WHERE branches.id = ?',
            $compiled->sql,
        );

        $this->assertSame([100], $compiled->bindings);
    }

    public function test_draft_dataset_cannot_be_compiled_for_execution(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage(
            'Dataset [transactions] is not available to this user.',
        );

        $this->compiler($this->transactionRegistry(DatasetStatus::DRAFT))->compileFor(
            $this->user(EmployeeRole::AUDITOR),
            new TransactionDatasetSource,
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['transaction_reference'],
            ),
        );
    }

    public function test_administrator_cannot_compile_business_data_query(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->compiler($this->transactionRegistry(DatasetStatus::ACTIVE))->compileFor(
            $this->user(EmployeeRole::ADMINISTRATOR),
            new TransactionDatasetSource,
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['transaction_reference'],
            ),
        );
    }

    public function test_country_manager_fails_closed_without_country_assignment(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage(
            'No authorized row scope is available for dataset [transactions].',
        );

        $this->compiler($this->transactionRegistry(DatasetStatus::ACTIVE))->compileFor(
            $this->user(EmployeeRole::COUNTRY_MANAGER),
            new TransactionDatasetSource,
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['transaction_reference'],
            ),
        );
    }

    private function compiler(
        DatasetRegistry $registry,
    ): AuthorizedDatasetQueryCompiler {
        $access = new DatasetAccess($registry);

        return new AuthorizedDatasetQueryCompiler(
            $access,
            new DatasetRowScopeResolver($access),
            new DatasetQueryCompiler(
                new FilterCompiler,
                $registry,
            ),
        );
    }

    private function transactionRegistry(
        DatasetStatus $status,
    ): DatasetRegistry {
        $definition = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        return new DatasetRegistry([
            new DatasetDefinition(
                key: $definition->key,
                label: $definition->label,
                description: $definition->description,
                grain: $definition->grain,
                status: $status,
                dimensions: $definition->dimensions(),
                measures: $definition->measures(),
            ),
        ]);
    }

    private function user(
        EmployeeRole $role,
        ?int $branchId = null,
    ): User {
        $employee = (new Employee)->forceFill([
            'branch_id' => $branchId,
            'role' => $role,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $user = new User;
        $user->setRelation('employee', $employee);

        return $user;
    }
}
