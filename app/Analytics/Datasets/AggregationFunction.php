<?php

namespace App\Analytics\Datasets;

enum AggregationFunction: string
{
    case COUNT = 'count';
    case SUM = 'sum';
    case AVERAGE = 'average';
    case MINIMUM = 'minimum';
    case MAXIMUM = 'maximum';
}
