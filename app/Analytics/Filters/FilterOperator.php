<?php

namespace App\Analytics\Filters;

enum FilterOperator: string
{
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';
    case IN = 'in';
    case NOT_IN = 'not_in';
    case BEFORE = 'before';
    case AFTER = 'after';
    case BETWEEN = 'between';
    case IS_NULL = 'is_null';
    case IS_NOT_NULL = 'is_not_null';
}
