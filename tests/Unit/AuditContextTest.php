<?php

namespace Tests\Unit;

use App\Analytics\Auditing\AuditContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuditContextTest extends TestCase
{
    public function test_safe_operational_metadata_is_canonicalized(): void
    {
        $context = AuditContext::from([
            'row_count' => 25,
            'period' => 'last_30_days',
            'format' => 'csv',
            'dimension_count' => 3,
            'widget_count' => 3,
        ]);

        $this->assertSame(
            [
                'dimension_count' => 3,
                'format' => 'csv',
                'period' => 'last_30_days',
                'row_count' => 25,
                'widget_count' => 3,
            ],
            $context->toArray(),
        );
    }

    public function test_banking_and_query_values_are_rejected(): void
    {
        foreach ([
            'account_number',
            'customer_name',
            'filter_value',
            'query_results',
            'sql',
            'exception_message',
        ] as $unsafeKey) {
            try {
                AuditContext::from([
                    $unsafeKey => 'sensitive-value',
                ]);

                $this->fail(
                    "Expected audit key [{$unsafeKey}] to be rejected.",
                );
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_nested_payloads_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AuditContext::from([
            'reason_code' => [
                'secret' => 'value',
            ],
        ]);
    }
}
