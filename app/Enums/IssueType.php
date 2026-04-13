<?php

namespace App\Enums;

enum IssueType: string
{
    case Exception = 'exception';
    case Performance = 'performance';
    case Other = 'other';
}
