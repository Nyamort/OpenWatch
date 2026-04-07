---
name: ingestion-validator
description: Adds a new record type to the ingestion pipeline — validator class extending BaseRecordValidator, registration in RecordValidatorRegistry, and a unit test.
---

# Ingestion Validator

This project ingests telemetry records via a pipeline. Each record has a `t` field (type). Adding a new type requires 3 changes: a validator class, registry registration, and a test.

## Architecture

```
Ingested payload (gzip JSON)
  └─ RecordValidatorRegistry::validate($record)
       └─ looks up $record['t'] → validator class
       └─ BaseRecordValidator::validate() — checks base fields + type-specific fields
            └─ requiredFields() — abstract, defined per-type
```

**Base required fields** (every record must have these):
- `v` — version
- `t` — type
- `timestamp`
- `deploy`
- `server`
- Plus either `_group` or `trace_id` (at least one must be present)

## Step-by-Step

### 1. Create the Validator class

File: `app/Services/Ingestion/Validators/{TypePascalCase}RecordValidator.php`

```php
<?php

namespace App\Services\Ingestion\Validators;

class {TypePascalCase}RecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return [
            'execution_source',
            'execution_id',
            // type-specific required fields here
            'field_a',
            'field_b',
        ];
    }
}
```

Look at existing validators for reference on which fields make sense:
- `ExceptionRecordValidator`: `execution_source`, `execution_id`, `class`, `message`, `trace`
- `QueryRecordValidator`: `execution_source`, `execution_id`, `sql`, `duration_ms`
- `RequestRecordValidator`: `method`, `path`, `status_code`, `duration_ms`
- `UserRecordValidator`: `user_id`

### 2. Register in RecordValidatorRegistry

File: `app/Services/Ingestion/RecordValidatorRegistry.php`

Add to the `$validators` array:

```php
private array $validators = [
    // ... existing entries ...
    '{type-slug}' => {TypePascalCase}RecordValidator::class,
];
```

Also add the `use` import at the top.

**Important:** The key must match the `t` field value sent in the payload (kebab-case slug, e.g. `cache-event`, `queued-job`).

### 3. Write the unit test

File: `tests/Unit/Services/Ingestion/Validators/{TypePascalCase}RecordValidatorTest.php`

```php
<?php

use App\Services\Ingestion\Validators\{TypePascalCase}RecordValidator;

it('validates a valid {type} record', function (): void {
    $validator = new {TypePascalCase}RecordValidator;

    $record = [
        'v'       => 1,
        't'       => '{type-slug}',
        'timestamp' => now()->toISOString(),
        'deploy'  => 'production',
        'server'  => 'web-01',
        'trace_id' => 'abc123',
        // type-specific fields:
        'field_a' => 'value',
        'field_b' => 'value',
    ];

    expect($validator->validate($record))->toBeTrue();
});

it('rejects a {type} record missing required field', function (): void {
    $validator = new {TypePascalCase}RecordValidator;

    $record = [
        'v'        => 1,
        't'        => '{type-slug}',
        'timestamp' => now()->toISOString(),
        'deploy'   => 'production',
        'server'   => 'web-01',
        'trace_id' => 'abc123',
        // 'field_a' intentionally missing
        'field_b'  => 'value',
    ];

    expect($validator->validate($record))->toBeFalse();
});

it('rejects a {type} record missing both group and trace_id', function (): void {
    $validator = new {TypePascalCase}RecordValidator;

    $record = [
        'v'        => 1,
        't'        => '{type-slug}',
        'timestamp' => now()->toISOString(),
        'deploy'   => 'production',
        'server'   => 'web-01',
        // no _group or trace_id
        'field_a'  => 'value',
        'field_b'  => 'value',
    ];

    expect($validator->validate($record))->toBeFalse();
});
```

### 4. Test the registry integration (optional but recommended)

```php
it('RecordValidatorRegistry validates a {type} record', function (): void {
    $registry = new \App\Services\Ingestion\RecordValidatorRegistry;

    $record = [/* valid record */];

    expect($registry->validate($record))->toBeTrue();
});
```

## Key Conventions

- Validator class name: `{TypePascalCase}RecordValidator` — e.g., `QueuedJobRecordValidator`
- Registry key: kebab-case slug matching the `t` field — e.g., `queued-job`
- `requiredFields()` returns only **type-specific** fields; base fields are checked by `BaseRecordValidator`
- `validate()` returns `bool` — never throws, never logs

## Run After Creating

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --filter={TypePascalCase}RecordValidator
```
