<?php

namespace App\Analytics\Warehouse\Facts;

interface TransactionFactSource
{
    /**
     * @return iterable<array{
     *     source_transaction_id: int,
     *     account_key: int,
     *     branch_key: int,
     *     booking_date_key: int|null,
     *     value_date_key: int|null,
     *     booked_at: string|null,
     *     transaction_reference: string,
     *     transaction_type: string,
     *     category: string,
     *     amount: string,
     *     transaction_currency: string,
     *     direction: string,
     *     transaction_status: string,
     *     source_updated_at: string|null
     * }>
     */
    public function rows(): iterable;
}
