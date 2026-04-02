<?php

namespace App\Providers;

use App\Services\ClickHouse\ClickHouseService;
use Illuminate\Support\ServiceProvider;

class ClickHouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClickHouseService::class, function (): ClickHouseService {
            return new ClickHouseService(
                host: config('clickhouse.host'),
                httpPort: config('clickhouse.http_port'),
                database: config('clickhouse.database'),
                username: config('clickhouse.username'),
                password: config('clickhouse.password'),
            );
        });
    }
}
