<?php

namespace App\Analytics\Warehouse\Dimensions;

use DateMalformedStringException;
use DateTimeImmutable;
use InvalidArgumentException;
use Iterator;

final readonly class DateDimensionRangeGenerator
{
    public function __construct(
        private DateDimensionRowFactory $rowFactory,
    ) {}

    /**
     * @return Iterator<int, array<string, int|string|bool>>
     *
     * @throws DateMalformedStringException
     */
    public function generate(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
    ): Iterator {
        $current = $start->setTime(0, 0);
        $last = $end->setTime(0, 0);

        if ($current > $last) {
            throw new InvalidArgumentException(
                'The date-dimension start date cannot be after its end date.',
            );
        }

        while ($current <= $last) {
            yield $this->rowFactory->create($current);

            $current = $current->modify('+1 day');
        }
    }
}
