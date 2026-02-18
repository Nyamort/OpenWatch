# Task T-008: Organization Audit and Compliance Controls
- Domain: `organisation`, shared
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement immutable organization audit log, retention policies, and compliance constraints for critical organization operations.

## How to implement
1. Create structured audit tables and event dispatcher hooks for org critical actions.
2. Add retention worker with policy-driven purge/anonymization.
3. Add filters by event type, actor, organization, and date for admin views.
4. Include actor, target, org, IP, UA, timestamps in every audit record.

## Architecture implications
- **Context**: central observability and compliance.
- **Storage**: partitioned immutable tables for audits, retention indexes.
- **Pipeline**: synchronous write for critical events; async processing for anonymization.
- **Security**: restrict audit mutation and tamper-resistant writing pattern.

## Acceptance checkpoints
- org-critical operations generate audit events with full metadata.
- retention policy can be tested in non-prod.

## Done criteria
- `FR-ORG-043` to `FR-ORG-047` implemented.
