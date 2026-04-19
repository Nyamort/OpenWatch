<?php

namespace App\Enums;

enum TimelineEventKind: string
{
    case IssueCreated = 'issue_created';
    case StatusChanged = 'status_changed';
    case AssignmentChanged = 'assignment_changed';
    case Commented = 'comment';
}
