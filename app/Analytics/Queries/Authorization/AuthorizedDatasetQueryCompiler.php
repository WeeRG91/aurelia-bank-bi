<?php

namespace App\Analytics\Queries\Authorization;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetFieldAccess;
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
        private DatasetFieldAccess $fieldAccess,
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

        $this->authorizeFields($user, $query);

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

    private function authorizeFields(
        User $user,
        DatasetQuery $query,
    ): void {
        foreach ($query->dimensions as $dimension) {
            if (
                ! $this->fieldAccess->canUseDimension(
                    $user,
                    $query->dataset,
                    $dimension,
                )
            ) {
                throw new AuthorizationException(
                    "Dimension [{$dimension}] is not available for dataset [{$query->dataset->value}].",
                );
            }
        }

        foreach ($query->measures as $measure) {
            if (
                ! $this->fieldAccess->canUseMeasure(
                    $user,
                    $query->dataset,
                    $measure,
                )
            ) {
                throw new AuthorizationException(
                    "Measure [{$measure}] is not available for dataset [{$query->dataset->value}].",
                );
            }
        }

        foreach ($query->filters as $filter) {
            if (
                ! $this->fieldAccess->canUseDimension(
                    $user,
                    $query->dataset,
                    $filter->dimension,
                )
            ) {
                throw new AuthorizationException(
                    "Filter dimension [{$filter->dimension}] is not available for dataset [{$query->dataset->value}].",
                );
            }
        }
    }
}
