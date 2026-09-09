<?php

namespace App\Analytics\Scheduling;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class NextReportRunCalculator
{
    public function calculate(
        ReportScheduleFrequency $frequency,
        string $runTime,
        DateTimeZone $timezone,
        DateTimeImmutable $after,
        ?int $weekday = null,
        ?int $dayOfMonth = null,
    ): CarbonImmutable {
        [$hour, $minute, $second] = $this->parseTime($runTime);

        $this->validateConfiguration(
            $frequency,
            $weekday,
            $dayOfMonth,
        );

        $localAfter = CarbonImmutable::instance($after)
            ->setTimezone($timezone);

        $candidate = match ($frequency) {
            ReportScheduleFrequency::DAILY => $localAfter
                ->startOfDay()
                ->setTime($hour, $minute, $second),

            ReportScheduleFrequency::WEEKLY => $localAfter
                ->startOfDay()
                ->addDays(
                    ($weekday - $localAfter->isoWeekday() + 7) % 7,
                )
                ->setTime($hour, $minute, $second),

            ReportScheduleFrequency::MONTHLY => $localAfter
                ->startOfMonth()
                ->addDays($dayOfMonth - 1)
                ->setTime($hour, $minute, $second),
        };

        if ($candidate->lessThanOrEqualTo($localAfter)) {
            $candidate = match ($frequency) {
                ReportScheduleFrequency::DAILY => $candidate->addDay(),
                ReportScheduleFrequency::WEEKLY => $candidate->addWeek(),
                ReportScheduleFrequency::MONTHLY => $candidate->addMonth(),
            };
        }

        return $candidate->utc();
    }

    /**
     * @return array{int, int, int}
     */
    private function parseTime(string $runTime): array
    {
        if (
            preg_match(
                '/^(?<hour>[01][0-9]|2[0-3]):(?<minute>[0-5][0-9])(?::(?<second>[0-5][0-9]))?$/',
                $runTime,
                $matches,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                "Invalid report run time [{$runTime}].",
            );
        }

        return [
            (int) $matches['hour'],
            (int) $matches['minute'],
            isset($matches['second'])
                ? (int) $matches['second']
                : 0,
        ];
    }

    private function validateConfiguration(
        ReportScheduleFrequency $frequency,
        ?int $weekday,
        ?int $dayOfMonth,
    ): void {
        $valid = match ($frequency) {
            ReportScheduleFrequency::DAILY => $weekday === null
                && $dayOfMonth === null,

            ReportScheduleFrequency::WEEKLY => $weekday !== null
                && $weekday >= 1
                && $weekday <= 7
                && $dayOfMonth === null,

            ReportScheduleFrequency::MONTHLY => $weekday === null
                && $dayOfMonth !== null
                && $dayOfMonth >= 1
                && $dayOfMonth <= 28,
        };

        if (! $valid) {
            throw new InvalidArgumentException(
                "Invalid configuration for [{$frequency->value}] recurrence.",
            );
        }
    }
}
