# Task T-009: Project and Environment Lifecycle
- Domain: `projects`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-005`, `T-006`, `T-029`

## Description
Implement project CRUD (name/slug unique per org, owner org scoping), environment CRUD under each project (`production`, `staging`, `development` or custom), computed project health status from environment signals, and exclusion of archived resources from active routes.

## How to implement
1. Create `projects` migration: `id`, `organization_id`, `name`, `slug` (unique per org), `description`, `archived_at`, timestamps.
2. Create `environments` migration: `id`, `project_id`, `name`, `slug` (unique per project), `type` enum, `status` (active/inactive/archived), timestamps.
3. Implement `CreateProject`, `UpdateProject`, `ArchiveProject` actions with org quota check (call `QuotaService` from T-007).
4. Implement `CreateEnvironment`, `UpdateEnvironment`, `ArchiveEnvironment` actions scoped to their project.
5. Add computed `health_status` on project: aggregate environment signals (last_ingested_at, error_rate, token_active) into `healthy | degraded | inactive`. Refresh via `RefreshProjectHealth` job on a schedule or post-ingest hook.
6. Scope all project/environment queries to the active org via the global scope from T-005.
7. Write feature tests: create project, duplicate slug rejection, archive/unarchive, environment creation, health status computation.

## Key files to create or modify
- `database/migrations/xxxx_create_projects_table.php`
- `database/migrations/xxxx_create_environments_table.php`
- `app/Models/Project.php`
- `app/Models/Environment.php`
- `app/Actions/Projects/CreateProject.php`
- `app/Actions/Projects/UpdateProject.php`
- `app/Actions/Projects/ArchiveProject.php`
- `app/Actions/Projects/CreateEnvironment.php`
- `app/Actions/Projects/ArchiveEnvironment.php`
- `app/Jobs/RefreshProjectHealth.php`
- `app/Http/Controllers/Projects/ProjectController.php`
- `app/Http/Controllers/Projects/EnvironmentController.php`
- `resources/js/pages/projects/` — project list and detail pages
- `tests/Feature/Projects/ProjectLifecycleTest.php`

## Acceptance criteria
- [ ] Project is created with a name and slug unique within the organization
- [ ] Duplicate project slug within the same org is rejected
- [ ] Archived projects are excluded from active project lists
- [ ] Environment is created under a project with a unique name within that project
- [ ] Project health status reflects environment signals and updates within 1 minute of a change
- [ ] A user cannot create a project in an org they are not a member of
- [ ] Quota limit from T-007 blocks project creation when the plan limit is reached

## Related specs
- [Functional spec](../specs.md) — `FR-PROJ-001` to `FR-PROJ-019`
- [Technical spec](../specs-technical.md)
