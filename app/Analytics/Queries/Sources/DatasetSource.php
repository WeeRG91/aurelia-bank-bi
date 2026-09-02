<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Datasets\DatasetKey;

interface DatasetSource
{
    public function dataset(): DatasetKey;

    public function baseTable(): string;

    public function branchScopeColumn(): string;

    /**
     * @return array<string, DimensionSource>
     */
    public function dimensionSources(): array;

    public function dimensionSource(string $dimension): DimensionSource;

    /**
     * @return array<string, MeasureSource>
     */
    public function measureSources(): array;

    public function measureSource(string $measure): MeasureSource;

    /**
     * @return list<JoinDefinition>
     */
    public function joins(): array;
}
