<?php

namespace App\Enums;

enum CardState: string
{
    case New = 'new';
    case Learning = 'learning';
    case Review = 'review';
    case Relearning = 'relearning';
}
