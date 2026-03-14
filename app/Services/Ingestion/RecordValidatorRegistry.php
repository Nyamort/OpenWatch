<?php

namespace App\Services\Ingestion;

use App\Services\Ingestion\Validators\CacheEventRecordValidator;
use App\Services\Ingestion\Validators\CommandRecordValidator;
use App\Services\Ingestion\Validators\ExceptionRecordValidator;
use App\Services\Ingestion\Validators\JobAttemptRecordValidator;
use App\Services\Ingestion\Validators\LogRecordValidator;
use App\Services\Ingestion\Validators\MailRecordValidator;
use App\Services\Ingestion\Validators\NotificationRecordValidator;
use App\Services\Ingestion\Validators\OutgoingRequestRecordValidator;
use App\Services\Ingestion\Validators\QueryRecordValidator;
use App\Services\Ingestion\Validators\QueuedJobRecordValidator;
use App\Services\Ingestion\Validators\RequestRecordValidator;
use App\Services\Ingestion\Validators\ScheduledTaskRecordValidator;
use App\Services\Ingestion\Validators\UserRecordValidator;
use InvalidArgumentException;

class RecordValidatorRegistry
{
    /**
     * @var array<string, class-string>
     */
    private array $validators = [
        'request' => RequestRecordValidator::class,
        'query' => QueryRecordValidator::class,
        'cache-event' => CacheEventRecordValidator::class,
        'command' => CommandRecordValidator::class,
        'log' => LogRecordValidator::class,
        'notification' => NotificationRecordValidator::class,
        'mail' => MailRecordValidator::class,
        'queued-job' => QueuedJobRecordValidator::class,
        'job-attempt' => JobAttemptRecordValidator::class,
        'scheduled-task' => ScheduledTaskRecordValidator::class,
        'outgoing-request' => OutgoingRequestRecordValidator::class,
        'exception' => ExceptionRecordValidator::class,
        'user' => UserRecordValidator::class,
    ];

    /**
     * Validate a record using the appropriate type validator.
     *
     * @param  array<string, mixed>  $record
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $record): bool
    {
        $type = $record['t'] ?? null;

        if (! isset($this->validators[$type])) {
            throw new InvalidArgumentException("Unknown record type: {$type}");
        }

        $validatorClass = $this->validators[$type];
        $validator = new $validatorClass;

        return $validator->validate($record);
    }
}
