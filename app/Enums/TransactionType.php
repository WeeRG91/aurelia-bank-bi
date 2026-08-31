<?php

namespace App\Enums;

enum TransactionType: string
{
    case TRANSFER = 'transfer';
    case CARD_PAYMENT = 'card_payment';
    case CASH_WITHDRAWAL = 'cash_withdrawal';
    case CASH_DEPOSIT = 'cash_deposit';
    case DIRECT_DEBIT = 'direct_debit';
    case FEE = 'fee';
    case INTEREST = 'interest';
    case LOAN_PAYMENT = 'loan_payment';
}
