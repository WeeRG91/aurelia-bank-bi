<?php

namespace App\Analytics\Dashboards;

final readonly class DashboardKpiComparisonValue
{
    /**
     * @param  array<string, bool|int|string|null>  $context
     */
    public function __construct(
        public array $context,
        public int|string|null $current,
        public int|string|null $previous,
        public DashboardKpiComparisonState $state,
    ) {}

    /**
     * @return array{
     *     context: array<string, bool|int|string|null>,
     *     current: int|string|null,
     *     previous: int|string|null,
     *     state: string
     * }
     */
    public function toArray(): array
    {
        return [
            'context' => $this->context,
            'current' => $this->current,
            'previous' => $this->previous,
            'state' => $this->state->value,
        ];
    }
}
