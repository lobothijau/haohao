<?php

namespace App\Enums;

enum ContentSource: string
{
    case Manual = 'manual';
    case AiGenerated = 'ai_generated';
    case Adapted = 'adapted';
}
