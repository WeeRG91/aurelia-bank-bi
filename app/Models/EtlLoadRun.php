<?php

namespace App\Models;

use App\Analytics\Warehouse\Etl\EtlRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'pipeline',
    'status',
    'extracted_rows',
    'loaded_rows',
    'failure_code',
    'started_at',
    'finished_at',
])]
final class EtlLoadRun extends Model
{
    use HasUlids;

    protected $connection = 'warehouse';

    protected $table = 'etl_load_runs';

    protected function casts(): array
    {
        return [
            'status' => EtlRunStatus::class,
            'extracted_rows' => 'integer',
            'loaded_rows' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }
}
