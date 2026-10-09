<?php

namespace App\Analytics\Warehouse\Etl;

use App\Models\EtlLoadRun;
use DateTimeImmutable;

interface EtlRunStore
{
    public function create(
        string $pipeline,
        DateTimeImmutable $startedAt,
    ): EtlLoadRun;

    public function save(EtlLoadRun $run): void;
}
