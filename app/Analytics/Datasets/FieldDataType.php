<?php

namespace App\Analytics\Datasets;

enum FieldDataType: string
{
    case STRING = 'string';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case BOOLEAN = 'boolean';
    case INTEGER = 'integer';
    case DECIMAL = 'decimal';
}
