<?php

namespace App\Actions\Analytics\Mail;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use Illuminate\Support\Facades\DB;

class BuildMailDetailData
{
    /**
     * Fetch a single mail record with all fields.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, int $mailId): array
    {
        $mail = DB::table('extraction_mails')
            ->where('id', $mailId)
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->first();

        if ($mail === null) {
            abort(404, 'Mail record not found.');
        }

        return (new AnalyticsResponseBuilder)
            ->withSummary((array) $mail)
            ->build();
    }
}
