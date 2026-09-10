<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Time\RelativeDatePreset;
use InvalidArgumentException;

final readonly class DashboardWidgetDefinition
{
    /**
     * @param  list<string>  $dimensions
     * @param  list<string>  $measures
     */
    public function __construct(
        public DashboardWidgetKey $key,
        public string $label,
        public string $description,
        public DatasetKey $dataset,
        public array $dimensions,
        public array $measures,
        public string $relativeDateDimension,
        public RelativeDatePreset $relativeDatePreset,
        public int $limit,
    ) {
        if (
            trim($this->label) === ''
            || trim($this->description) === ''
        ) {
            throw new InvalidArgumentException(
                'Dashboard widget labels and descriptions cannot be blank.',
            );
        }

        if ($this->dimensions === [] && $this->measures === []) {
            throw new InvalidArgumentException(
                'A dashboard widget requires at least one dimension or measure.',
            );
        }

        $this->assertSemanticKeys(
            $this->dimensions,
            'dimensions',
        );

        $this->assertSemanticKeys(
            $this->measures,
            'measures',
        );

        if (
            preg_match(
                '/^[a-z][a-z0-9_]*$/',
                $this->relativeDateDimension,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'The relative date dimension must be a safe semantic key.',
            );
        }

        if ($this->limit < 1 || $this->limit > 500) {
            throw new InvalidArgumentException(
                'Dashboard widget limits must be between 1 and 500.',
            );
        }
    }

    /**
     * @return array{
     *     dimensions: list<string>,
     *     measures: list<string>,
     *     filters: list<never>,
     *     relative_date: array{
     *         dimension: string,
     *         preset: string
     *     },
     *     limit: int
     * }
     */
    public function reportDefinition(
        ?RelativeDatePreset $relativeDatePreset = null,
    ): array {
        return [
            'dimensions' => $this->dimensions,
            'measures' => $this->measures,
            'filters' => [],
            'relative_date' => [
                'dimension' => $this->relativeDateDimension,
                'preset' => (
                    $relativeDatePreset ?? $this->relativeDatePreset
                )->value,
            ],
            'limit' => $this->limit,
        ];
    }

    /**
     * @param  list<string>  $keys
     */
    private function assertSemanticKeys(
        array $keys,
        string $field,
    ): void {
        if (! array_is_list($keys)) {
            throw new InvalidArgumentException(
                "Dashboard widget {$field} must be a list.",
            );
        }

        foreach ($keys as $key) {
            if (
                preg_match('/^[a-z][a-z0-9_]*$/', $key) !== 1
            ) {
                throw new InvalidArgumentException(
                    "Dashboard widget {$field} must contain safe semantic keys.",
                );
            }
        }

        if (count(array_unique($keys)) !== count($keys)) {
            throw new InvalidArgumentException(
                "Dashboard widget {$field} must be unique.",
            );
        }
    }
}
