<?php

namespace App\Analytics\Warehouse\Dimensions;

enum DimensionVersionAction: string
{
    case CREATE_INITIAL = 'create_initial';
    case KEEP_CURRENT = 'keep_current';
    case CREATE_SUCCESSOR = 'create_successor';
}
