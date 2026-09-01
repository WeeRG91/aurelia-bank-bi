<?php

namespace App\Analytics\Datasets;

enum DatasetKey: string
{
    case CUSTOMER_OVERVIEW = 'customer_overview';
    case ACCOUNT_BALANCES = 'account_balances';
    case TRANSACTIONS = 'transactions';
    case CARD_ACTIVITY = 'card_activity';
    case LOANS = 'loans';
    case LOAN_REPAYMENTS = 'loan_repayments';
    case BRANCH_PERFORMANCE = 'branch_performance';
}
