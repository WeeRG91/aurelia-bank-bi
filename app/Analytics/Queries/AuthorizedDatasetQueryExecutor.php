<?php

namespace App\Analytics\Queries;

use App\Analytics\Queries\Authorization\AuthorizedDatasetQueryCompiler;
use App\Analytics\Queries\Sources\DatasetSource;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class AuthorizedDatasetQueryExecutor
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private AuthorizedDatasetQueryCompiler $compiler,
        private DatabaseManager $database,
    ) {}

    /**
     * @return list<object>
     *
     * @throws Throwable
     */
    public function executeFor(
        User $user,
        DatasetSource $source,
        DatasetQuery $query,
    ): array {
        $compiled = $this->compiler->compileFor(
            $user,
            $source,
            $query,
        );

        $connection = $this->database->connection('pgsql');

        return $connection->transaction(
            function (
                ConnectionInterface $transactionConnection,
            ) use ($compiled): array {
                $transactionConnection->statement(
                    'SET TRANSACTION READ ONLY',
                );

                return $transactionConnection->select(
                    $compiled->sql,
                    $compiled->bindings,
                );
            },
            attempts: 1,
        );
    }
}
