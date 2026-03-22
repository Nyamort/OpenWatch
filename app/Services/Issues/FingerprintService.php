<?php

namespace App\Services\Issues;

class FingerprintService
{
    /**
     * Generate a fingerprint for an exception.
     */
    public function forException(string $class, string $message, ?string $file = null, ?int $line = null): string
    {
        $normalized = preg_replace('/\b\d+\b/', '?', $message);

        return hash('sha256', implode('|', [
            strtolower(trim($class)),
            strtolower(trim($normalized)),
            $file ? basename($file) : '',
            $line ?? '',
        ]));
    }

    /**
     * Generate a fingerprint for a request.
     */
    public function forRequest(string $method, string $routePath, int $statusCode): string
    {
        return hash('sha256', implode('|', [
            strtoupper($method),
            $routePath,
            $statusCode,
        ]));
    }

    /**
     * Generate a fingerprint for a job.
     */
    public function forJob(string $jobClass, string $queue): string
    {
        return hash('sha256', implode('|', [
            $jobClass,
            $queue,
        ]));
    }
}
