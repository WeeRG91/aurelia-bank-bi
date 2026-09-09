<?php

namespace Tests\Unit;

use App\Analytics\Auditing\AuditRecorder;
use App\Analytics\Auditing\DatabaseAuditRecorder;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tests\TestCase;

final class AuditRecorderBindingTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test_database_recorder_is_bound_to_the_contract(): void
    {
        $this->assertInstanceOf(
            DatabaseAuditRecorder::class,
            $this->app->make(AuditRecorder::class),
        );
    }
}
