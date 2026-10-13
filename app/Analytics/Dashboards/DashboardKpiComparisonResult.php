<?php

namespace App\Analytics\Dashboards;

final readonly class DashboardKpiComparisonResult
{
    /**
     * @param  array<string, bool|int|string|null>  $context
     */
    public function __construct(
        public array $context,
        public int|string|null $current,
        public int|string|null $previous,
        public DashboardKpiComparisonState $state,
        public ?string $percentageChange,
    ) {}

    /**
     * @return array{
     *     context: array<string, bool|int|string|null>,
     *     current: int|string|null,
     *     previous: int|string|null,
     *     state: string,
     *     percentage_change: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'context' => $this->context,
            'current' => $this->current,
            'previous' => $this->previous,
            'state' => $this->state->value,
            'percentage_change' => $this->percentageChange,
        ];
    }
}
