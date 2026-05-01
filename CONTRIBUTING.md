# Contributing to Vaultic

Thank you for your interest in contributing.

## Development Setup

1. Fork the repository.
2. Create a feature branch from `master`.
3. Install dependencies.
4. Run tests before opening a pull request.

```bash
composer install
composer test
```

## Branch and Commit Guidelines

1. Use focused branches with descriptive names.
2. Keep commits small and atomic.
3. Prefer conventional-style commit messages when possible.

Examples:

- `fix(auth): handle challenge replay edge case`
- `docs(release): improve compatibility matrix`

## Coding Standards

1. Keep architecture boundaries clear:
   Controller -> Service -> Repository -> Contracts.
2. Avoid breaking public APIs without documenting migration guidance.
3. Include or update tests for behavior changes.
4. Keep docs in sync with supported versions.

## Pull Request Checklist

1. Tests pass locally.
2. Backward compatibility impact is described.
3. Changelog or release notes impact is clear.
4. Documentation has been updated when needed.

## Version Compatibility Policy

Vaultic maintains release lines mapped to Laravel and PHP versions. New features target modern lines first and are backported only when safe.
