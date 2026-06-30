<?php

namespace App\Enums;

enum VisitorStatus: string
{
    case Inside = 'inside';
    case Exited = 'exited';
}
