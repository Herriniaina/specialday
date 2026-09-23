<?php

namespace App\Domain\Shared;

enum FilterOperator: string
{
    case EQ = 'eq';
    case NOT_EQ = 'noteq';
    case IN = 'in';
    case NOT_IN = 'notin';
    case CONTAINS = 'contains';
}
