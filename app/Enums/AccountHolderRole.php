<?php

namespace App\Enums;

enum AccountHolderRole: string
{
    case PRIMARY = 'primary';
    case JOINT = 'joint';
}
