<?php

namespace App\Events;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Issue $issue,
        public readonly string $from,
        public readonly string $to,
        public readonly User $actor,
    ) {}
}
