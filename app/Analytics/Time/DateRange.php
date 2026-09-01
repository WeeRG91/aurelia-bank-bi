<?php

namespace App\Analytics\Time;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class DateRange
{
    public function __construct(
        public string $startDate,
        public string $endDate,
    ) {
        if (! $this->isValidDate($this->startDate)) {
            throw new InvalidArgumentException(
                "Invalid range start date [{$this->startDate}].",
            );
        }

        if (! $this->isValidDate($this->endDate)) {
            throw new InvalidArgumentException(
                "Invalid range end date [{$this->endDate}].",
            );
        }

        if ($this->startDate > $this->endDate) {
            throw new InvalidArgumentException(
                'Date range start must not be after its end.',
            );
        }
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false
            && $date->format('Y-m-d') === $value;
    }
}
