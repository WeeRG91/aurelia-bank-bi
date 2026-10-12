<?php

namespace App\Analytics\Warehouse\Dimensions;

use InvalidArgumentException;

final readonly class DimensionVersionDecision
{
    public function __construct(
        public DimensionVersionAction $action,
        public int $version,
    ) {}

    public static function decide(
        ?int $currentVersion,
        ?string $currentHash,
        string $incomingHash,
    ): self {
        self::assertHash($incomingHash);

        if (($currentVersion === null) !== ($currentHash === null)) {
            throw new InvalidArgumentException(
                'The current version and hash must either both be present or both be null.',
            );
        }

        if ($currentVersion === null) {
            return new self(
                DimensionVersionAction::CREATE_INITIAL,
                1,
            );
        }

        if ($currentVersion < 1) {
            throw new InvalidArgumentException(
                'The current dimension version must be positive.',
            );
        }

        self::assertHash($currentHash);

        if (hash_equals($currentHash, $incomingHash)) {
            return new self(
                DimensionVersionAction::KEEP_CURRENT,
                $currentVersion,
            );
        }

        return new self(
            DimensionVersionAction::CREATE_SUCCESSOR,
            $currentVersion + 1,
        );
    }

    private static function assertHash(string $hash): void
    {
        if (preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
            throw new InvalidArgumentException(
                'Dimension attribute hashes must be lowercase SHA-256 values.',
            );
        }
    }
}
