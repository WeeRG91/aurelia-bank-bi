<?php

namespace Tests\Unit;

use App\Analytics\Time\ReportingTimezone;
use DateInvalidTimeZoneException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ReportingTimezoneTest extends TestCase
{
    /**
     * @throws DateInvalidTimeZoneException
     */
    public function test_named_timezone_is_preserved_and_converted(): void
    {
        $timezone = new ReportingTimezone('Europe/Luxembourg');

        $this->assertSame('Europe/Luxembourg', $timezone->name);
        $this->assertSame(
            'Europe/Luxembourg',
            $timezone->toDateTimeZone()->getName(),
        );
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function test_utc_is_supported(): void
    {
        $timezone = new ReportingTimezone('UTC');

        $this->assertSame(
            'UTC',
            $timezone->toDateTimeZone()->getName(),
        );
    }

    #[DataProvider('invalidTimezoneNames')]
    public function test_invalid_or_ambiguous_timezone_is_rejected(
        string $name,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new ReportingTimezone($name);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTimezoneNames(): iterable
    {
        yield 'empty' => [''];
        yield 'misspelled city' => ['Europe/Luxemburg'];
        yield 'fixed offset' => ['+02:00'];
        yield 'abbreviation' => ['CET'];
    }

    public function test_reporting_timezone_is_final_and_immutable(): void
    {
        $reflection = new ReflectionClass(ReportingTimezone::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}
