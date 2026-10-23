<?php

namespace App\Enums;

enum ActivityType: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case Note = 'note';
    case NextActionSet = 'next_action_set';
    case SaleRecorded = 'sale_recorded';
}
