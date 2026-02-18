# Technical Specifications - Exception Detail Analytics

## 1. Data model and detail scope

Exception detail operates on a single `occurrence_group_key` within organization/project/environment scope.

Data loaded for the detail page:
- **Signature metadata**: resolved from `extraction_exception` using `occurrence_group_key` — provides `exception_class`, `handled` flag, `first_seen`, `last_seen`, occurrence count.
- **Latest stack trace**: loaded from `raw_payload` of the most recent occurrence matching the group key (stack frames, file paths, line numbers, runtime metadata).
- **Occurrences list**: paginated rows from `extraction_exception` filtered by `occurrence_group_key`, with optional `user_state` filter (all / authenticated / guest).
- **Impact stats**: COUNT(*) total, COUNT(DISTINCT user_id) users, COUNT(DISTINCT server_id) servers in active filters.

## 2. Detail query strategy

Resolution flow:
1. Validate `occurrence_group_key` belongs to `organization_id + project_id + environment_id` (403 if not).
2. Load summary stats: `first_seen`, `last_seen`, `handled` aggregate, occurrence counts, user/server impact.
3. Load latest stack trace: `SELECT raw_payload FROM telemetry_records WHERE grouping_key = ? ORDER BY ts_utc DESC LIMIT 1`.
4. Load distribution chart: occurrence counts bucketed by time, split by `handled`.
5. Load occurrence rows: paginated, sorted by `ts_utc DESC`, filtered by `user_state`.

User state filter maps:
- `all` → no user filter
- `authenticated` → `WHERE user_id IS NOT NULL`
- `guest` → `WHERE user_id IS NULL`

## 3. Stack trace rendering

Stack frames are stored in `raw_payload.trace` as an array:
```json
[
  { "file": "app/Services/PaymentService.php", "line": 42, "function": "charge", "class": "PaymentService" },
  { "file": "vendor/stripe/stripe-php/lib/ApiRequestor.php", "line": 182, "function": "request" }
]
```

Rendering rules:
- App frames (non-`vendor/`) rendered expanded by default.
- Vendor frames collapsed into a toggle group ("X vendor frames").
- File path shown relative to project root when possible.
- `expand/collapse` behavior managed client-side; no extra API call needed.

Runtime metadata chips (from `raw_payload`): `php_version`, `laravel_version`, `handled` badge.

## 4. API contracts

```
GET /analytics/exceptions/{group_key}
```

Query params: `period`, `from`, `to`, `user`, `user_state` (all | authenticated | guest), `page`, `per_page`.

Response:
```json
{
  "signature": {
    "group_key": "abc123",
    "exception_class": "App\\Exceptions\\PaymentException",
    "message_preview": "Payment declined...",
    "handled": false,
    "first_seen": "2026-01-01T00:00:00Z",
    "last_seen": "2026-03-14T12:00:00Z"
  },
  "summary": {
    "total": 42,
    "handled": 10,
    "unhandled": 32,
    "users": 8,
    "servers": 2
  },
  "series": [{ "bucket_start_utc": "...", "handled": 3, "unhandled": 7 }],
  "stack_trace": { "frames": [...], "php_version": "8.5.3", "laravel_version": "12.x" },
  "occurrences": {
    "rows": [{ "date": "...", "source": "request", "message": "...", "user": "user_123" }],
    "pagination": { "current_page": 1, "total": 42 }
  }
}
```

Fallback: if `group_key` is not found or unauthorized → redirect to exception list with `?error=not_found`.

## 5. Security and tenant isolation

- `occurrence_group_key` lookup always includes `organization_id` + `project_id` + `environment_id` scope.
- Stack trace content (file paths, code snippets) is never exposed cross-organization.
- "To Issue" action (`issue.create` permission check) passes `source_type=exception`, `source_fingerprint=group_key`.
- User identifiers in occurrence rows respect PII policy — shown as provided, not enriched.

## 6. Test strategy

Key feature tests:
- Detail page loads correctly for valid `group_key` within scope.
- Invalid `group_key` redirects to list with error guidance.
- Stack trace loads from latest occurrence; vendor frames are distinguishable.
- User state filter (`authenticated` / `guest`) correctly narrows occurrence rows and summary stats.
- Distribution chart reflects `user_state` filter (same as occurrence table).
- Cross-organization access denied for `group_key` from another org.
- "To Issue" action only visible for users with `issue.create` permission.
- Back navigation preserves original list filters.

## Related Resources

- **Functional Spec**: [exception-detail.md](./exception-detail.md)
- **List Page**: [exception-technical.md](./exception-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
