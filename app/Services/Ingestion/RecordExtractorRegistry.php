<?php

namespace App\Services\Ingestion;

use App\Services\Ingestion\Extractors\BaseRecordExtractor;
use App\Services\Ingestion\Extractors\CacheEventRecordExtractor;
use App\Services\Ingestion\Extractors\CommandRecordExtractor;
use App\Services\Ingestion\Extractors\ExceptionRecordExtractor;
use App\Services\Ingestion\Extractors\JobAttemptRecordExtractor;
use App\Services\Ingestion\Extractors\LogRecordExtractor;
use App\Services\Ingestion\Extractors\MailRecordExtractor;
use App\Services\Ingestion\Extractors\NotificationRecordExtractor;
use App\Services\Ingestion\Extractors\OutgoingRequestRecordExtractor;
use App\Services\Ingestion\Extractors\QueryRecordExtractor;
use App\Services\Ingestion\Extractors\QueuedJobRecordExtractor;
use App\Services\Ingestion\Extractors\RequestRecordExtractor;
use App\Services\Ingestion\Extractors\ScheduledTaskRecordExtractor;
use App\Services\Ingestion\Extractors\UserRecordExtractor;

class RecordExtractorRegistry
{
    /**
     * @var array<string, class-string<BaseRecordExtractor>>
     */
    private array $extractors = [
        'request' => RequestRecordExtractor::class,
        'query' => QueryRecordExtractor::class,
        'cache-event' => CacheEventRecordExtractor::class,
        'command' => CommandRecordExtractor::class,
        'log' => LogRecordExtractor::class,
        'notification' => NotificationRecordExtractor::class,
        'mail' => MailRecordExtractor::class,
        'queued-job' => QueuedJobRecordExtractor::class,
        'job-attempt' => JobAttemptRecordExtractor::class,
        'scheduled-task' => ScheduledTaskRecordExtractor::class,
        'outgoing-request' => OutgoingRequestRecordExtractor::class,
        'exception' => ExceptionRecordExtractor::class,
        'user' => UserRecordExtractor::class,
    ];

    public function for(string $type): ?BaseRecordExtractor
    {
        if (! isset($this->extractors[$type])) {
            return null;
        }

        return new $this->extractors[$type];
    }
}
