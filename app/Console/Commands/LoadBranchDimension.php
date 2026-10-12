<?php

namespace App\Console\Commands;

use App\Analytics\Warehouse\Dimensions\BranchDimensionLoader;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('warehouse:load-branch-dimension')]
#[Description('Load the warehouse branch dimension')]
class LoadBranchDimension extends Command
{
    public function __construct(
        private readonly BranchDimensionLoader $loader,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws DateMalformedStringException
     */
    public function handle(): int
    {
        $loadedRows = $this->loader->load(
            new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC'),
            )
        );

        $this->components->info(
            "Branch dimension loaded successfully: {$loadedRows} rows.",
        );

        return self::SUCCESS;
    }
}
