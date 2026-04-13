<?php

namespace App\Enums;

enum IssuePriority: string
{
    case None = 'none';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
