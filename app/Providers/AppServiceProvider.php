<?php

namespace App\Providers;

use App\Events\TelemetryBatchIngested;
use App\Listeners\HandleExceptionTelemetry;
use App\Models\AlertRule;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Project;
use App\Policies\AlertRulePolicy;
use App\Policies\IssuePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ProjectPolicy;
use App\Services\Ingestion\ConcurrencyLimiter;
use App\Services\Ingestion\RecordHandlerRegistry;
use App\Services\Ingestion\SessionTokenService;
use App\Timeline\TimelineEventRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SessionTokenService::class);
        $this->app->singleton(ConcurrencyLimiter::class);
        $this->app->singleton(RecordHandlerRegistry::class);
        $this->app->singleton(TimelineEventRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(AlertRule::class, AlertRulePolicy::class);
        Gate::policy(Issue::class, IssuePolicy::class);

        Event::listen(TelemetryBatchIngested::class, HandleExceptionTelemetry::class);

        Relation::morphMap($this->app->make(TimelineEventRegistry::class)->morphMap());

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
