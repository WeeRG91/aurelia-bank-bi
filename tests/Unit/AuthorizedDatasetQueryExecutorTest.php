<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DatasetStatus;
use App\Analytics\Queries\Authorization\AuthorizedDatasetQueryCompiler;
use App\Analytics\Queries\Authorization\DatasetRowScopeResolver;
use App\Analytics\Queries\AuthorizedDatasetQueryExecutor;
use App\Analytics\Queries\Compilation\FilterCompiler;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\DatasetQueryCompiler;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Throwable;

class AuthorizedDatasetQueryExecutorTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function test_authorized_query_executes_inside_a_read_only_transaction(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(ConnectionInterface::class);

        $database->expects($this->once())
            ->method('connection')
            ->with('pgsql')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('statement')
            ->with('SET TRANSACTION READ ONLY')
            ->willReturn(true);

        $connection->expects($this->once())
            ->method('select')
            ->with(
                $this->stringContains(
                    'WHERE branches.id = ?',
                ),
                [42, 100],
            )
            ->willReturn([
                (object) [
                    'transaction_reference' => 'TXN-EXAMPLE',
                    'currency' => 'EUR',
                ],
            ]);

        $connection->expects($this->once())
            ->method('transaction')
            ->with(
                $this->isInstanceOf(Closure::class),
                1,
            )
            ->willReturnCallback(
                function (
                    Closure $callback,
                    int $attempts,
                ) use ($connection): array {
                    $this->assertSame(1, $attempts);

                    return $callback($connection);
                },
            );

        $rows = $this->executor(
            $this->activeRegistry(),
            $database,
        )->executeFor(
            $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: 42,
            ),
            new TransactionDatasetSource,
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: [
                    'transaction_reference',
                    'currency',
                ],
            ),
        );

        $this->assertCount(1, $rows);
        $this->assertSame(
            'TXN-EXAMPLE',
            $rows[0]->transaction_reference,
        );
    }

    /**
     * @throws Throwable
     */
    public function test_unauthorized_query_never_requests_a_database_connection(): void
    {
        $database = $this->createMock(DatabaseManager::class);

        $database->expects($this->never())
            ->method('connection');

        $this->expectException(AuthorizationException::class);

        $this->executor(
            $this->activeRegistry(),
            $database,
        )->executeFor(
            $this->user(EmployeeRole::ADMINISTRATOR),
            new TransactionDatasetSource,
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['transaction_reference'],
            ),
        );
    }

    private function executor(
        DatasetRegistry $registry,
        DatabaseManager $database,
    ): AuthorizedDatasetQueryExecutor {
        $access = new DatasetAccess($registry);

        $compiler = new AuthorizedDatasetQueryCompiler(
            $access,
            new DatasetRowScopeResolver($access),
            new DatasetQueryCompiler(
                new FilterCompiler,
                $registry,
            ),
        );

        return new AuthorizedDatasetQueryExecutor(
            $compiler,
            $database,
        );
    }

    private function activeRegistry(): DatasetRegistry
    {
        $draft = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        return new DatasetRegistry([
            new DatasetDefinition(
                key: $draft->key,
                label: $draft->label,
                description: $draft->description,
                grain: $draft->grain,
                status: DatasetStatus::ACTIVE,
                dimensions: $draft->dimensions(),
                measures: $draft->measures(),
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
