<?php

namespace App\Services\Ingestion;

use App\Services\Ingestion\Handlers\BaseRecordHandler;
use App\Services\Ingestion\Handlers\CacheEventRecordHandler;
use App\Services\Ingestion\Handlers\CommandRecordHandler;
use App\Services\Ingestion\Handlers\ExceptionRecordHandler;
use App\Services\Ingestion\Handlers\JobAttemptRecordHandler;
use App\Services\Ingestion\Handlers\LogRecordHandler;
use App\Services\Ingestion\Handlers\MailRecordHandler;
use App\Services\Ingestion\Handlers\NotificationRecordHandler;
use App\Services\Ingestion\Handlers\OutgoingRequestRecordHandler;
use App\Services\Ingestion\Handlers\QueryRecordHandler;
use App\Services\Ingestion\Handlers\QueuedJobRecordHandler;
use App\Services\Ingestion\Handlers\RequestRecordHandler;
use App\Services\Ingestion\Handlers\ScheduledTaskRecordHandler;
use App\Services\Ingestion\Handlers\UserRecordHandler;
use InvalidArgumentException;

class RecordHandlerRegistry
{
    /**
     * @var array<string, class-string<BaseRecordHandler>>
     */
    private array $handlers = [
        'request' => RequestRecordHandler::class,
        'query' => QueryRecordHandler::class,
        'cache-event' => CacheEventRecordHandler::class,
        'command' => CommandRecordHandler::class,
        'log' => LogRecordHandler::class,
        'notification' => NotificationRecordHandler::class,
        'mail' => MailRecordHandler::class,
        'queued-job' => QueuedJobRecordHandler::class,
        'job-attempt' => JobAttemptRecordHandler::class,
        'scheduled-task' => ScheduledTaskRecordHandler::class,
        'outgoing-request' => OutgoingRequestRecordHandler::class,
        'exception' => ExceptionRecordHandler::class,
        'user' => UserRecordHandler::class,
    ];

    /**
     * @throws InvalidArgumentException
     */
    public function for(string $type): BaseRecordHandler
    {
        if (! isset($this->handlers[$type])) {
            throw new InvalidArgumentException("Unknown record type: {$type}");
        }

        return new $this->handlers[$type];
    }
}
