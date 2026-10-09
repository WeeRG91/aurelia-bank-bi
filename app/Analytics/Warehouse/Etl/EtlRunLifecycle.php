<?php

namespace App\Analytics\Warehouse\Etl;

use App\Models\EtlLoadRun;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

final class EtlRunLifecycle
{
    public function succeed(
        EtlLoadRun $run,
        int $extractedRows,
        int $loadedRows,
        DateTimeImmutable $finishedAt,
    ): void {
        $this->assertRunning($run);
        $this->assertRowCounts($extractedRows, $loadedRows);
        $this->assertFinishedAt($run, $finishedAt);

        $run->forceFill([
            'status' => EtlRunStatus::SUCCEEDED,
            'extracted_rows' => $extractedRows,
            'loaded_rows' => $loadedRows,
            'failure_code' => null,
            'finished_at' => $finishedAt,
        ]);
    }

    public function fail(
        EtlLoadRun $run,
        int $extractedRows,
        int $loadedRows,
        string $failureCode,
        DateTimeImmutable $finishedAt,
    ): void {
        $this->assertRunning($run);
        $this->assertRowCounts($extractedRows, $loadedRows);
        $this->assertFailureCode($failureCode);
        $this->assertFinishedAt($run, $finishedAt);

        $run->forceFill([
            'status' => EtlRunStatus::FAILED,
            'extracted_rows' => $extractedRows,
            'loaded_rows' => $loadedRows,
            'failure_code' => $failureCode,
            'finished_at' => $finishedAt,
        ]);
    }

    private function assertRunning(EtlLoadRun $run): void
    {
        if ($run->status !== EtlRunStatus::RUNNING) {
            throw new LogicException(
                'Only a running ETL load can transition to a terminal state.',
            );
        }
    }

    private function assertRowCounts(int $extractedRows, int $loadedRows): void
    {
        if (
            $extractedRows < 0 ||
            $loadedRows < 0 ||
            $loadedRows > $extractedRows
        ) {
            throw new InvalidArgumentException(
                'ETL row counts must be non-negative and loaded rows cannot exceed extracted rows.',
            );
        }
    }

    private function assertFailureCode(string $failureCode): void
    {
        if (
            preg_match(
                '/^[a-z][a-z0-9_]{2,99}$/D',
                $failureCode,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'The ETL failure code has an invalid format.',
            );
        }
    }

    private function assertFinishedAt(
        EtlLoadRun $run,
        DateTimeImmutable $finishedAt
    ): void {
        if ($run->started_at === null || $finishedAt < $run->started_at) {
            throw new InvalidArgumentException(
                'The ETL run cannot finish before it started.',
            );
        }
    }
}
