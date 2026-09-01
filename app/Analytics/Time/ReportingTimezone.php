<?php

namespace App\Analytics\Time;

use DateInvalidTimeZoneException;
use DateTimeZone;
use InvalidArgumentException;

final readonly class ReportingTimezone
{
    public function __construct(
        public string $name,
    ) {
        if (! in_array($this->name, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException(
                "Unknown reporting timezone [{$this->name}].",
            );
        }
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function toDateTimeZone(): DateTimeZone
    {
        return new DateTimeZone($this->name);
    }
}
