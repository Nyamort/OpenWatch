---
name: analytics-page
description: Adds a new analytics page following this project's pattern — AnalyticsController base class with resolveContext/buildPeriod, Inertia::defer for slow data, Action-based data building, and a React page with AnalyticsLayout + Deferred skeletons.
---

# Analytics Page

This project has ~12 analytics resource types (requests, queries, exceptions, cache events, etc.) all following the same architecture. Use this skill when adding a new analytics resource or a new view (index/route/show) to an existing one.

## Architecture

```
Controller (extends AnalyticsController)
  └─ resolveContext() → AnalyticsContext (org + project + env)
  └─ buildPeriod()    → PeriodResult (start/end timestamps)
  └─ BuildXxxData::handle() → array of graph/stats/rows
  └─ Inertia::defer() for slow data props

React Page
  └─ AnalyticsLayout (period selector + breadcrumbs)
  └─ <Deferred data={['graph','stats']} fallback={<Skeleton />}>
  └─ <Deferred data={['rows','pagination']} fallback={<Skeleton />}>
```

## Step-by-Step

### 1. Controller (extends AnalyticsController)

```php
<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\{Resource}\Build{Resource}IndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class {Resource}Controller extends AnalyticsController
{
    public function __construct(
        private readonly Build{Resource}IndexData $buildIndex,
    ) {}

    public function index(Request $request, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'default_column');
        $direction = (string) $request->query('direction', 'desc');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $page): array {
            return $data ??= $this->buildIndex->handle(
                ctx: $ctx,
                period: $period,
                sort: $sort,
                direction: $direction,
                page: $page,
            );
        };

        return Inertia::render('analytics/{resource}/index', [
            'graph'      => Inertia::defer(fn () => $resolve()['graph']),
            'stats'      => Inertia::defer(fn () => $resolve()['stats']),
            'rows'       => Inertia::defer(fn () => $resolve()['rows']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period'     => $request->query('period', '24h'),
            'sort'       => $sort,
            'direction'  => $direction,
        ]);
    }
}
```

**Important:** The `$data = null; $resolve = fn() => $data ??= ...` pattern prevents building the expensive query twice when multiple `Inertia::defer` callbacks share the same data source.

### 2. Action class (data builder)

```php
<?php

namespace App\Actions\Analytics\{Resource};

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;

class Build{Resource}IndexData
{
    /**
     * @return array{
     *   graph: list<array{label: string, value: int}>,
     *   stats: array{total: int, ...},
     *   rows: list<array{...}>,
     *   pagination: array{current_page: int, last_page: int, per_page: int, total: int},
     * }
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $sort,
        string $direction,
        int $page,
    ): array {
        // Query using $ctx->organization, $ctx->project, $ctx->environment
        // Use $period->start and $period->end for time range filtering

        return [
            'graph'      => [],
            'stats'      => [],
            'rows'       => [],
            'pagination' => [],
        ];
    }
}
```

### 3. React page (resources/js/pages/analytics/{resource}/index.tsx)

```tsx
import { Deferred, Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { {Resource}Charts } from './partials/{resource}-charts';
import { {Resource}Table } from './partials/{resource}-table';
import type { GraphBucket, Pagination, Stats } from './types';

interface Props {
    graph?: GraphBucket[];
    stats?: Stats;
    rows?: RowType[];
    pagination?: Pagination;
    period: string;
    sort: string;
    direction: string;
}

const breadcrumbs = [{ title: '{Resource Label}', href: '#' }];

function ChartsSkeleton() {
    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {[0, 1].map((i) => (
                <div key={i} className="h-[206px] animate-pulse rounded-xl border bg-muted/40" />
            ))}
        </div>
    );
}

function TableSkeleton() {
    return (
        <div className="flex flex-col gap-3">
            <div className="h-10 w-64 animate-pulse rounded-lg bg-muted/40" />
            <div className="flex flex-col gap-1.5">
                <div className="h-11 animate-pulse rounded-lg bg-muted/40" />
                {Array.from({ length: 8 }).map((_, i) => (
                    <div key={i} className="h-11 animate-pulse rounded-lg bg-muted/20" />
                ))}
            </div>
        </div>
    );
}

export default function {Resource}Index({ graph, stats, rows, pagination, period, sort, direction }: Props) {
    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <{Resource}Charts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred data={['rows', 'pagination']} fallback={<TableSkeleton />}>
                <{Resource}Table rows={rows!} pagination={pagination!} sort={sort} direction={direction} />
            </Deferred>
        </AnalyticsLayout>
    );
}
```

### 4. Types file (resources/js/pages/analytics/{resource}/types.ts)

```ts
export type SortKey = 'column_a' | 'column_b';
export type SortDir = 'asc' | 'desc';

export interface Stats {
    total: number;
    // ... other aggregates
}

export interface RowType {
    id: string;
    // ... columns
}

export interface GraphBucket {
    label: string;
    value: number;
}

export interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
```

## Key Conventions

- Always use `Inertia::defer()` for graph, stats, rows, and pagination — these are slow queries
- Never skip the `$data ??=` memoization pattern when multiple defers share data
- `AnalyticsContext` exposes: `$ctx->organization`, `$ctx->project`, `$ctx->environment`
- `PeriodResult` exposes: `$period->start` (Carbon), `$period->end` (Carbon), `$period->label` (string)
- Breadcrumbs are static arrays defined at module level (not inside component)
- Skeleton components must be defined in the same file, not imported

## Run After Creating

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --filter={Resource}
```
