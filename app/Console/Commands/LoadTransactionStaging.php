<?php

namespace App\Console\Commands;

use App\Analytics\Warehouse\Etl\EtlPipelineExecutor;
use App\Analytics\Warehouse\Etl\TransactionStagingPipeline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('warehouse:load-transactions')]
#[Description('Extract operational transactions into warehouse staging')]
final class LoadTransactionStaging extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(
        EtlPipelineExecutor $executor,
        TransactionStagingPipeline $pipeline
    ): int {
        try {
            $run = $executor->execute($pipeline);
        } catch (Throwable $exception) {
            report($exception);

            $this->components->error(
                'Transaction staging ETL failed. Inspect the application log and ETL load runs.',
            );

            return self::FAILURE;
        }

        $this->components->info(
            sprintf(
                'ETL run %s succeeded: %d extracted, %d loaded.',
                $run->getKey(),
                $run->extracted_rows,
                $run->loaded_rows,
            ),
        );

        return self::SUCCESS;
    }
}
