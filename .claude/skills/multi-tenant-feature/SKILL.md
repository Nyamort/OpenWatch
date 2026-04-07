---
name: multi-tenant-feature
description: Scaffolds a full multi-tenant CRUD feature scoped to Organization → Project → Environment, with policy, Form Request, controller, Inertia pages, Wayfinder routes, and Pest feature tests.
---

# Multi-Tenant Feature

This project uses a 3-level tenancy: **Organization → Project → Environment**. Every resource lives within this hierarchy. Use this skill when adding any new resource that belongs to an environment (or project/org).

## Route Hierarchy

```
/organizations/{organization}/projects/{project}/environments/{environment}/{resource}
```

Routes are defined in `routes/web.php` under the existing nested route groups.

## Step-by-Step

### 1. Create the Model + Migration + Factory

```bash
php artisan make:model {Resource} --migration --factory --no-interaction
```

The model should include:
- `organization_id`, `project_id`, `environment_id` foreign keys
- `BelongsTo` relationships with return type hints
- Casts in `casts()` method (not `$casts` property)

```php
class {Resource} extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }
}
```

### 2. Create the Policy

```bash
php artisan make:policy {Resource}Policy --model={Resource} --no-interaction
```

Follow the existing `AlertRulePolicy` pattern — check organization membership and role:

```php
public function viewAny(User $user, Organization $organization): bool
{
    return $user->belongsToOrganization($organization);
}

public function create(User $user, Organization $organization): bool
{
    return $user->hasOrganizationRole($organization, ['owner', 'editor']);
}

public function update(User $user, {Resource} $resource): bool
{
    return $user->hasOrganizationRole($resource->organization, ['owner', 'editor']);
}

public function delete(User $user, {Resource} $resource): bool
{
    return $user->hasOrganizationRole($resource->organization, ['owner', 'editor']);
}
```

### 3. Create Form Request classes

```bash
php artisan make:request Store{Resource}Request --no-interaction
php artisan make:request Update{Resource}Request --no-interaction
```

Check sibling Form Requests in `app/Http/Requests/` for whether this project uses array or string rule syntax.

### 4. Create Action classes

One action per operation (create-action skill covers the pattern in detail):

```
app/Actions/{Resource}/Create{Resource}.php
app/Actions/{Resource}/Update{Resource}.php
app/Actions/{Resource}/Delete{Resource}.php
```

### 5. Create the Controller

```bash
php artisan make:controller {Resource}Controller --no-interaction
```

- Inject Action classes via constructor
- Resolve `Organization`, `Project`, `Environment` from route model binding
- Use `$this->authorize()` with the policy
- Return `Inertia::render()` for GET, `redirect()->route()` for mutations

```php
class {Resource}Controller extends Controller
{
    public function __construct(
        private readonly Create{Resource} $create,
        private readonly Update{Resource} $update,
        private readonly Delete{Resource} $delete,
    ) {}

    public function index(Request $request, Organization $organization, Project $project, Environment $environment): Response
    {
        $this->authorize('viewAny', [Resource::class, $organization]);

        return Inertia::render('{resource}/index', [
            'resources' => {Resource}::query()
                ->where('environment_id', $environment->id)
                ->latest()
                ->get(),
        ]);
    }

    public function store(Store{Resource}Request $request, Organization $organization, Project $project, Environment $environment): RedirectResponse
    {
        $this->authorize('create', [$organization]);

        $this->create->handle($organization, $project, $environment, $request->user(), $request->validated());

        return redirect()->route('{resource}.index', [$organization, $project, $environment]);
    }
}
```

### 6. Register Routes

In `routes/web.php`, inside the existing environment-scoped route group:

```php
Route::resource('{resource}', {Resource}Controller::class);
```

Check existing route grouping structure for the correct nesting level.

### 7. React Pages

```
resources/js/pages/{resource}/index.tsx
resources/js/pages/{resource}/create.tsx
resources/js/pages/{resource}/edit.tsx
```

Use `AppLayout` as the outer layout, follow existing pages structure (e.g., `alert-rules/`).

### 8. Activate Wayfinder

After adding routes, run:

```bash
php artisan wayfinder:generate
```

Then import route helpers from `@/routes/` in React components (see `wayfinder-development` skill).

## Key Conventions

- Route model binding resolves `Organization`, `Project`, `Environment` automatically from URL slugs
- Always eager-load relationships to prevent N+1: `->with(['organization', 'project'])`
- `authorize()` calls use policy class-string syntax: `$this->authorize('create', [Resource::class, $organization])`
- Soft deletes: only add `SoftDeletes` if the resource needs restore functionality (check existing models)
- Audit logging: if actions affect org-level data, log via `OrganizationAuditEvent` (see `AuditLogger` service)

## Run After Creating

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --filter={Resource}
```
