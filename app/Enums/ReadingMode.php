<?php

namespace App\Enums;

enum ReadingMode: string
{
    case Full = 'full';
    case Sentence = 'sentence';
    case Focus = 'focus';
}
