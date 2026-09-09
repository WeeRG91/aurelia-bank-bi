<?php

namespace App\Analytics\Datasets;

use App\Enums\EmployeeRole;
use App\Models\User;

final readonly class DatasetFieldAccess
{
    public function __construct(
        private DatasetRegistry $registry,
        private DatasetAccess $datasetAccess,
    ) {}

    public function canUseDimension(
        User $user,
        DatasetKey|string $dataset,
        string $dimensionKey,
    ): bool {
        $definition = $this->definitionForUse($user, $dataset);
        $dimension = $definition?->findDimension($dimensionKey);

        return $dimension !== null
            && $this->allowsDimension($user, $dimension);
    }

    public function canUseMeasure(
        User $user,
        DatasetKey|string $dataset,
        string $measureKey,
    ): bool {
        $definition = $this->definitionForUse($user, $dataset);
        $measure = $definition?->findMeasure($measureKey);

        if (
            $definition === null
            || $measure === null
            || ! $this->allowsMeasure($user, $measure)
        ) {
            return false;
        }

        foreach ($measure->requiredContextDimensions() as $dimensionKey) {
            $dimension = $definition->findDimension($dimensionKey);

            if (
                $dimension === null
                || ! $this->allowsDimension($user, $dimension)
            ) {
                return false;
            }
        }

        return true;
    }

    public function canUseDefinition(
        User $user,
        DatasetKey|string $dataset,
        array $definition,
    ): bool {
        foreach ($definition['dimensions'] ?? [] as $dimension) {
            if (
                ! is_string($dimension)
                || ! $this->canUseDimension(
                    $user,
                    $dataset,
                    $dimension,
                )
            ) {
                return false;
            }
        }

        foreach ($definition['measures'] ?? [] as $measure) {
            if (
                ! is_string($measure)
                || ! $this->canUseMeasure(
                    $user,
                    $dataset,
                    $measure,
                )
            ) {
                return false;
            }
        }

        foreach ($definition['filters'] ?? [] as $filter) {
            $dimension = is_array($filter)
                ? ($filter['dimension'] ?? null)
                : null;

            if (
                ! is_string($dimension)
                || ! $this->canUseDimension(
                    $user,
                    $dataset,
                    $dimension,
                )
            ) {
                return false;
            }
        }

        $relativeDate = $definition['relative_date'] ?? null;

        if ($relativeDate !== null) {
            $dimension = is_array($relativeDate)
                ? ($relativeDate['dimension'] ?? null)
                : null;

            if (
                ! is_string($dimension)
                || ! $this->canUseDimension(
                    $user,
                    $dataset,
                    $dimension,
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<DimensionDefinition>
     */
    public function dimensionsFor(
        User $user,
        DatasetKey|string $dataset,
    ): array {
        $definition = $this->definitionForUse($user, $dataset);

        if ($definition === null) {
            return [];
        }

        return array_values(
            array_filter(
                $definition->dimensions(),
                fn (DimensionDefinition $definition): bool => $this
                    ->allowsDimension($user, $definition)
            ),
        );
    }

    /**
     * @return list<MeasureDefinition>
     */
    public function measuresFor(
        User $user,
        DatasetKey|string $dataset,
    ): array {
        $definition = $this->definitionForUse($user, $dataset);

        if ($definition === null) {
            return [];
        }

        return array_values(
            array_filter(
                $definition->measures(),
                fn (MeasureDefinition $measure): bool => $this
                    ->canUseMeasure(
                        $user,
                        $definition->key,
                        $measure->key,
                    ),
            ),
        );
    }

    private function definitionForUse(
        User $user,
        DatasetKey|string $dataset,
    ): ?DatasetDefinition {
        if (! $this->datasetAccess->canUse($user, $dataset)) {
            return null;
        }

        return $this->registry->find($dataset);
    }

    private function allowsDimension(
        User $user,
        DimensionDefinition $dimension,
    ): bool {
        $role = $user->employee?->role;

        if (! $role instanceof EmployeeRole) {
            return false;
        }

        return match ($dimension->sensitivity) {
            SensitivityLevel::INTERNAL => true,

            SensitivityLevel::CONFIDENTIAL => $dimension->kind !== DimensionKind::IDENTIFIER
                || in_array(
                    $role,
                    [
                        EmployeeRole::BRANCH_MANAGER,
                        EmployeeRole::COUNTRY_MANAGER,
                        EmployeeRole::RISK_ANALYST,
                        EmployeeRole::AUDITOR,
                    ],
                    true,
                ),

            SensitivityLevel::RESTRICTED => in_array(
                $role,
                [
                    EmployeeRole::RISK_ANALYST,
                    EmployeeRole::AUDITOR,
                ],
                true,
            ),
        };
    }

    private function allowsMeasure(
        User $user,
        MeasureDefinition $measure,
    ): bool {
        $role = $user->employee?->role;

        if (! $role instanceof EmployeeRole) {
            return false;
        }

        return match ($measure->sensitivity) {
            SensitivityLevel::INTERNAL,
            SensitivityLevel::CONFIDENTIAL => true,

            SensitivityLevel::RESTRICTED => in_array(
                $role,
                [
                    EmployeeRole::RISK_ANALYST,
                    EmployeeRole::AUDITOR,
                ],
                true,
            ),
        };
    }
}
