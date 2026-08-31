<?php

namespace App\Enums;

enum EmployeeRole: string
{
    case BRANCH_ANALYST = 'branch_analyst';
    case BRANCH_MANAGER = 'branch_manager';
    case COUNTRY_MANAGER = 'country_manager';
    case FINANCE_ANALYST = 'finance_analyst';
    case RISK_ANALYST = 'risk_analyst';
    case AUDITOR = 'auditor';
    case ADMINISTRATOR = 'administrator';
}
