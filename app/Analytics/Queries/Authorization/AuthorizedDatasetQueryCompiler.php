<?php

namespace App\Analytics\Queries\Authorization;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Queries\CompiledQuery;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\DatasetQueryCompiler;
use App\Analytics\Queries\Sources\DatasetSource;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use LogicException;

final readonly class AuthorizedDatasetQueryCompiler
{
    public function __construct(
        private DatasetAccess $datasetAccess,
        private DatasetRowScopeResolver $scopeResolver,
        private DatasetQueryCompiler $queryCompiler,
    ) {}

    public function compileFor(
        User $user,
        DatasetSource $source,
        DatasetQuery $query,
    ): CompiledQuery {
        if ($source->dataset() !== $query->dataset) {
            throw new LogicException(
                "Query dataset [{$query->dataset->value}] does not match source dataset [{$source->dataset()->value}].",
            );
        }

        if (! $this->datasetAccess->canUse($user, $query->dataset)) {
            throw new AuthorizationException(
                "Dataset [{$query->dataset->value}] is not available to this user.",
            );
        }

        $scope = $this->scopeResolver->resolve(
            $user,
            $query->dataset,
        );

        if ($scope->type === RowScopeType::DENIED) {
            throw new AuthorizationException(
                "No authorized row scope is available for dataset [{$query->dataset->value}].",
            );
        }

        return $this->queryCompiler->compile(
            $source,
            $query,
            $scope,
        );
    }
}
