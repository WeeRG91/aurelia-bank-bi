<?php

namespace App\Console\Commands;

use App\Analytics\Warehouse\Facts\TransactionFactLoader;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('warehouse:load-transaction-facts')]
#[Description('Load the warehouse transaction fact table')]
class LoadTransactionFacts extends Command
{
    public function __construct(
        private readonly TransactionFactLoader $loader,
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
            "Transaction facts loaded successfully: {$loadedRows} rows.",
        );

        return self::SUCCESS;
    }
}
