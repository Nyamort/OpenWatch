---
name: create-action
description: Creates a single-responsibility Action class following this project's pattern — thin controller delegates to handle() method, with PHPDoc array shape, DB::transaction if needed, event dispatch, and a Pest feature test.
---

# Create Action Class

This project uses an **Action pattern**: controllers stay thin and delegate all business logic to dedicated Action classes in `app/Actions/{Domain}/`.

## Pattern Overview

- One public `handle()` method with typed parameters and return type
- PHPDoc `@param array{...}` shape for array parameters
- `DB::transaction()` for operations requiring atomicity
- Event dispatch via `Event::dispatch()` or `EventClass::dispatch()`
- No constructor injection unless truly shared dependencies

## File Structure

```
app/Actions/{Domain}/{ActionName}.php
tests/Feature/{Domain}/{ActionName}Test.php
```

## Step-by-Step

### 1. Identify the domain and name

- Domain matches the subdirectory: `Issues`, `Analytics/Request`, `Organization`, `Project`, `Alerts`, etc.
- Name is a verb phrase: `CreateIssue`, `UpdateAlertRule`, `BulkUpdateIssues`, `RevokeProjectToken`

### 2. Create the Action class

```php
<?php

namespace App\Actions\{Domain};

use App\Events\{EventClass};
use App\Models\{ModelA};
use App\Models\{ModelB};
use Illuminate\Support\Facades\DB;

class {ActionName}
{
    /**
     * {Description of what this action does}.
     *
     * @param array{
     *   field_one: string,
     *   field_two?: string|null,
     * } $data
     */
    public function handle(
        ModelA $modelA,
        ModelB $modelB,
        array $data,
    ): {ReturnType} {
        return DB::transaction(function () use ($modelA, $modelB, $data): {ReturnType} {
            // business logic here

            {EventClass}::dispatch($result, $actor);

            return $result;
        });
    }
}
```

### 3. Use the action in a controller

Inject via constructor and call `->handle()`:

```php
public function __construct(
    private readonly {ActionName} $action,
) {}

public function store(StoreRequest $request): RedirectResponse
{
    $this->action->handle($model, $request->validated());

    return redirect()->route('...');
}
```

### 4. Write the Pest feature test

```php
<?php

use App\Actions\{Domain}\{ActionName};
use App\Models\{ModelA};

it('{describes what action does}', function (): void {
    $modelA = {ModelA}::factory()->create();

    $result = app({ActionName}::class)->handle($modelA, [
        'field_one' => 'value',
    ]);

    expect($result)->{field}->toBe('expected');
    expect({ModelA}::count())->toBe(1);
});
```

## Key Conventions in This Codebase

- `DB::transaction()` is required when creating related models (e.g., Issue + IssueActivity + IssueSource)
- Activity logging: create an `IssueActivity` record inside the transaction for state changes
- Event dispatch happens **after** the transaction commits (outside the closure) — or use `afterCommit` listeners
- Array data shapes always have PHPDoc `@param array{...}` blocks
- Return type is always explicit — never omit it

## Run After Creating

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --filter={ActionName}
```
