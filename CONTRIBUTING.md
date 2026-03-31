# Contributing to Nightwatch

Thank you for your interest in contributing! Here's everything you need to know to get started.

## Code of conduct

Please be respectful and constructive in all interactions. We follow the [Contributor Covenant](https://www.contributor-covenant.org/) code of conduct.

## Getting started

1. **Fork** the repository and clone your fork locally.
2. Follow the [installation steps in the README](README.md#installation) to set up a local environment.
3. Create a feature branch from `main`:
   ```bash
   git checkout -b feat/your-feature-name
   ```

## Development workflow

### Running tests

Every change must be covered by tests. Run the full suite before submitting:

```bash
php artisan test --compact
```

Use `--filter` to run only the tests relevant to your change:

```bash
php artisan test --compact --filter=YourFeatureTest
```

### Code style

PHP code is formatted with [Laravel Pint](https://laravel.com/docs/pint). Run it before committing:

```bash
vendor/bin/pint --dirty
```

TypeScript/React code is linted with ESLint and formatted with Prettier:

```bash
npm run lint
npm run format
```

### Frontend assets

After making frontend changes, rebuild the assets:

```bash
npm run build
# or, for development with HMR:
npm run dev
```

### Route bindings

If you add or modify Laravel routes, regenerate the TypeScript bindings:

```bash
php artisan wayfinder:generate
```

## Submitting a pull request

1. Ensure all tests pass and code style checks are clean.
2. Keep pull requests focused — one feature or fix per PR.
3. Write a clear PR description explaining **what** the change does and **why**.
4. Reference any related issues with `Closes #123` or `Fixes #123`.
5. Squash trivial fixup commits before requesting review.

## Reporting bugs

Please use the [bug report template](.github/ISSUE_TEMPLATE/bug_report.md) when opening a new issue. Include as much detail as possible: PHP/Node version, steps to reproduce, expected vs. actual behaviour.

## Suggesting features

Open a [feature request](.github/ISSUE_TEMPLATE/feature_request.md) and describe the use case and proposed solution. We prefer discussing design before implementation for larger changes.

## Security vulnerabilities

Please **do not** open public issues for security vulnerabilities. Follow the process in [SECURITY.md](SECURITY.md).
