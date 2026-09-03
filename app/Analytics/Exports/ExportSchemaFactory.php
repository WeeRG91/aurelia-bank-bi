<?php

namespace App\Analytics\Exports;

use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Queries\DatasetQuery;

final readonly class ExportSchemaFactory
{
    public function __construct(
        private DatasetRegistry $datasetRegistry,
    ) {}

    public function forQuery(DatasetQuery $query): array
    {
        $dataset = $this->datasetRegistry->get($query->dataset);

        $columns = [];

        foreach ($query->dimensions as $dimensionKey) {
            $dimension = $dataset->dimension($dimensionKey);

            $columns[] = new ExportColumn(
                key: $dimension->key,
                label: $dimension->label,
                dataType: $dimension->dataType,
            );
        }

        foreach ($query->measures as $measureKey) {
            $measure = $dataset->measure($measureKey);

            $columns[] = new ExportColumn(
                key: $measure->key,
                label: $measure->label,
                dataType: $measure->dataType,
            );
        }

        return $columns;
    }
}
