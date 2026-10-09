<?php

namespace App\Analytics\Warehouse\Etl;

use App\Models\EtlLoadRun;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final class EloquentEtlRunStore implements EtlRunStore
{
    public function create(string $pipeline, DateTimeImmutable $startedAt): EtlLoadRun
    {
        if (
            preg_match(
                '/^[a-z][a-z0-9_]{2,99}$/D',
                $pipeline,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'The ETL pipeline identifier has an invalid format.',
            );
        }

        return EtlLoadRun::query()->create([
            'pipeline' => $pipeline,
            'status' => EtlRunStatus::RUNNING,
            'extracted_rows' => 0,
            'loaded_rows' => 0,
            'failure_code' => null,
            'started_at' => $startedAt,
            'finished_at' => null,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function save(EtlLoadRun $run): void
    {
        $run->saveOrFail();
    }
}
