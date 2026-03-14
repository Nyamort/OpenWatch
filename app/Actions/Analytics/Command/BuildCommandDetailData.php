<?php

namespace App\Actions\Analytics\Command;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use Illuminate\Support\Facades\DB;

class BuildCommandDetailData
{
    /**
     * Fetch a single command run with all fields.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, int $commandId): array
    {
        $command = DB::table('extraction_commands')
            ->where('id', $commandId)
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->first();

        if ($command === null) {
            abort(404, 'Command record not found.');
        }

        return (new AnalyticsResponseBuilder)
            ->withSummary((array) $command)
            ->build();
    }
}
