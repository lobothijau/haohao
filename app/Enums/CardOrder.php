<?php

namespace App\Enums;

enum CardOrder: string
{
    case NewFirst = 'new_first';
    case ReviewFirst = 'review_first';
    case Mixed = 'mixed';
}
