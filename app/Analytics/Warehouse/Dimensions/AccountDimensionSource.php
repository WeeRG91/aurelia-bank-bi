<?php

namespace App\Analytics\Warehouse\Dimensions;

interface AccountDimensionSource
{
    /**
     * @return iterable<array{
     *     source_account_id: int,
     *     account_number: string,
     *     account_type: string,
     *     account_currency: string,
     *     account_opened_at: string,
     *     account_closed_at: string|null,
     *     account_status: string
     * }>
     */
    public function rows(): iterable;
}
