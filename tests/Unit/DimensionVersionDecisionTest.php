<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\DimensionVersionAction;
use App\Analytics\Warehouse\Dimensions\DimensionVersionDecision;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DimensionVersionDecisionTest extends TestCase
{
    private const string FIRST_HASH =
        'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const string SECOND_HASH =
        'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    public function test_missing_current_version_creates_initial_version(): void
    {
        $decision = DimensionVersionDecision::decide(
            currentVersion: null,
            currentHash: null,
            incomingHash: self::FIRST_HASH,
        );

        $this->assertSame(
            DimensionVersionAction::CREATE_INITIAL,
            $decision->action,
        );

        $this->assertSame(1, $decision->version);
    }

    public function test_matching_hash_keeps_current_version(): void
    {
        $decision = DimensionVersionDecision::decide(
            currentVersion: 3,
            currentHash: self::FIRST_HASH,
            incomingHash: self::FIRST_HASH,
        );

        $this->assertSame(
            DimensionVersionAction::KEEP_CURRENT,
            $decision->action,
        );

        $this->assertSame(3, $decision->version);
    }

    public function test_changed_hash_creates_successor_version(): void
    {
        $decision = DimensionVersionDecision::decide(
            currentVersion: 3,
            currentHash: self::FIRST_HASH,
            incomingHash: self::SECOND_HASH,
        );

        $this->assertSame(
            DimensionVersionAction::CREATE_SUCCESSOR,
            $decision->action,
        );

        $this->assertSame(4, $decision->version);
    }

    public function test_incomplete_current_state_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DimensionVersionDecision::decide(
            currentVersion: 1,
            currentHash: null,
            incomingHash: self::FIRST_HASH,
        );
    }

    public function test_invalid_hash_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DimensionVersionDecision::decide(
            currentVersion: null,
            currentHash: null,
            incomingHash: 'not-a-sha256-hash',
        );
    }
}
