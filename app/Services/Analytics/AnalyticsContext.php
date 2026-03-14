<?php

namespace App\Services\Analytics;

use App\Models\Environment;
use App\Models\Organization;
use App\Models\Project;

class AnalyticsContext
{
    public function __construct(
        public readonly Organization $organization,
        public readonly Project $project,
        public readonly Environment $environment,
    ) {}
}
