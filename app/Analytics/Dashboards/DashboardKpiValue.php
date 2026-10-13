<?php

namespace App\Analytics\Dashboards;

final readonly class DashboardKpiValue
{
    /**
     * @param  array<string, bool|int|string|null>  $context
     */
    public function __construct(
        public array $context,
        public int|string|null $value,
    ) {}

    /**
     * @return array{
     *     context: array<string, bool|int|string|null>,
     *     value: int|string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'context' => $this->context,
            'value' => $this->value,
        ];
    }
}
