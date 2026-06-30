<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Paid = 'paid';
}
