<?php

namespace App\Enums;

enum EmployeeDepartment: string
{
    case BRANCH_OPERATIONS = 'branch_operations';
    case FINANCE = 'finance';
    case RISK = 'risk';
    case AUDIT = 'audit';
    case DATA_ANALYTICS = 'data_analytics';
    case ADMINISTRATION = 'administration';
    case MANAGEMENT = 'management';
}
