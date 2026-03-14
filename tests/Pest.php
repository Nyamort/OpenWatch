<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Insert a minimal telemetry_records row and return its auto-generated ID.
 * Use this to satisfy the unique FK constraint on extraction tables in tests.
 */
function nextTelemetryId(array $ctx = []): int
{
    return \Illuminate\Support\Facades\DB::table('telemetry_records')->insertGetId([
        'organization_id' => $ctx['org']->id ?? 1,
        'project_id' => $ctx['project']->id ?? 1,
        'environment_id' => $ctx['env']->id ?? 1,
        'record_type' => 'request',
        'recorded_at' => now(),
    ]);
}
