<?php

namespace App\Enums;

enum NotificationType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Goal = 'goal';
    case System = 'system';
}
