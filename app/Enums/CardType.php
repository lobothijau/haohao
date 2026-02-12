<?php

namespace App\Enums;

enum CardType: string
{
    case Recognition = 'recognition';
    case Recall = 'recall';
    case Listening = 'listening';
}
