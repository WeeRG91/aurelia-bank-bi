<?php

namespace App\Console\Commands;

use App\Analytics\Warehouse\Dimensions\AccountDimensionLoader;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('warehouse:load-account-dimension')]
#[Description('Load the warehouse account dimension')]
class LoadAccountDimension extends Command
{
    public function __construct(
        private readonly AccountDimensionLoader $loader,
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
            ),
        );

        $this->components->info(
            "Account dimension loaded successfully: {$loadedRows} rows.",
        );

        return self::SUCCESS;
    }
}
