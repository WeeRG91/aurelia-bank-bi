<?php

namespace App\Console\Commands;

use App\Analytics\Warehouse\Dimensions\DateDimensionLoader;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('warehouse:load-date-dimension')]
#[Description('Populate the warehouse date dimension')]
final class LoadDateDimension extends Command
{
    public function __construct(
        private readonly DateDimensionLoader $loader,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $start = $this->configuredDate(
                'analytics.warehouse.date_dimension_start',
            );

            $end = $this->configuredDate(
                'analytics.warehouse.date_dimension_end',
            );
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $loadedRows = $this->loader->load($start, $end);

        $this->components->info(
            "Date dimension loaded successfully: {$loadedRows} rows.",
        );

        return self::SUCCESS;
    }

    private function configuredDate(string $key): DateTimeImmutable
    {
        $value = config($key);

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException(
                "Configuration [{$key}] must contain a YYYY-MM-DD date.",
            );
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value,
            new DateTimeZone('UTC'),
        );

        $errors = DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || $date->format('Y-m-d') !== $value
            || (
                $errors !== false
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
        ) {
            throw new InvalidArgumentException(
                "Configuration [{$key}] must contain a valid YYYY-MM-DD date.",
            );
        }

        return $date;
    }
}
