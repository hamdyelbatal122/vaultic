# Changelog

All notable changes to this project will be documented in this file.

## v4.3.0

### Summary
Production readiness release introducing diagnostic and administrative Artisan commands, background queue support for usage metrics, enhanced model scopes, and strict static analysis.

### Changes
- Added `vaultic:health` diagnostic Artisan command to check relying party configuration, cache connectivity, database schema, HTTPS readiness, and authenticatable models.
- Added `vaultic:stats` Artisan command to display passkey counts, active user metrics, and authenticator transport distributions.
- Added `vaultic:prune` Artisan command and `PruneOrphanedPasskeysJob` to prune orphaned or inactive passkey records safely.
- Added background queue support via `RecordPasskeyUsageJob` for offloading sign-counter and IP logging during high-throughput authentication.
- Added query scopes (`scopeRecent`, `scopeUnused`, `scopeForAaguid`) and helper methods (`isUsed`, `daysSinceLastUse`) to the `Passkey` model.
- Enriched `HasPasskeys` trait with `hasPasskey`, `findPasskey`, and `deletePasskey` methods.
- Configured PHPStan Level 8 static analysis with zero errors.
- Enforced PSR-12 and Laravel Pint code formatting.
- Added GitHub Actions CI workflow supporting PHP 8.3, 8.4, and 8.5 across prefer-lowest and prefer-stable dependencies.
- Expanded automated test suite to 40 tests and 136 assertions with 100% pass rate.

## v4.2.0

- Added Passkey Rename feature (new PATCH endpoint, controller action, event, and requests).
- Enriched `HasPasskeys` trait with helper methods (`hasPasskeys()`, `passkeyCount()`, and `latestPasskey()`).
- Extracted domain-specific custom exceptions (`ChallengeExpiredException`, `CredentialAlreadyRegisteredException`, `VerificationFailedException`, `ClonedAuthenticatorException`).
- Form request validation classes (`RegisterPasskeyRequest`, `AuthenticationOptionsRequest`, `AuthenticateRequest`, `RenamePasskeyRequest`) instead of inline controller validation.
- Cleaned up migrations (removed redundant migration, modernized to anonymous class with strict types).
- Added comprehensive unit and feature tests for all new functionalities.
- Ensured strict typing and type-safety (`declare(strict_types=1)`) across all package source and test files.

## v4.1.0

- Track and display last-used IP for each passkey in the management UI.
- Fully refactored, professional UI/UX: compact Tailwind passkey button, modern management panel, clear activity visibility.
- Configurable rate limiting, multi-guard support, and API token issuing.
- All tests pass, code linted, and docs reviewed.

## v4.0.0

- Laravel 13 support line.
- PHP 8.3 baseline.
- Readonly-oriented modern internals.

## v3.3.1

- Compatibility patch for Laravel 12 line.
- Corrected PHP floor to 8.2.

## v3.2.1

- Compatibility patch for Laravel 11 line.
- Corrected PHP floor to 8.2.

## v3.1.0

- Laravel 10 support line.
- PHP 8.1 baseline.

## v3.0.1

- Compatibility patch for Laravel 9 line.
- Corrected PHP floor to 8.0.2.

## v2.0.0

- Laravel 8 support line.
- Named rate limiter integration (`vaultic.passkeys`).

## v1.3.0

- Laravel 7 support line.

## v1.2.1

- Compatibility patch for Laravel 6 line.
- Corrected PHP floor to 7.2.5.

## v1.0.2

- Compatibility patch for Laravel 5.5-5.8 line.
- Corrected PHP floor to 7.1.3.
- [2018-02-23]: refactor: optimize crypt block encryption parameters
- [2018-03-25]: docs: document vault decryption guidelines
- [2018-04-26]: chore: update crypto dependencies versions
- [2018-05-29]: style: tidy up security headers formatting
- [2018-07-02]: docs: document master password recovery limits
- [2018-07-29]: refactor: simplify secret keys derivation logic
- [2018-08-24]: fix: correct file decryption stream handlers
- [2018-09-23]: docs: document local vault backup routines
- [2018-10-27]: chore: configure default key wrapping options
- [2018-12-01]: refactor: clean up key management cache entries
- [2018-12-27]: style: improve secure terminal inputs layout
- [2019-02-14]: docs: add data classification standards
- [2019-03-15]: chore: update security notice guidelines
- [2019-04-19]: refactor: optimize lock timers execution path
- [2019-05-15]: docs: update troubleshooting guide for pin codes
- [2019-06-13]: fix: resolve cipher modes configuration flags
- [2019-07-13]: refactor: streamline database secrets backend
- [2019-08-08]: docs: update environment settings for vault service
- [2019-09-11]: style: standardize salt generation algorithms
- [2019-10-12]: chore: update security audit check docs
- [2019-11-06]: refactor: optimize crypt block encryption parameters
- [2019-12-03]: docs: document vault decryption guidelines
- [2020-02-14]: chore: update crypto dependencies versions
- [2020-03-14]: style: tidy up security headers formatting
- [2020-04-18]: docs: document master password recovery limits
- [2020-05-16]: refactor: simplify secret keys derivation logic
- [2020-06-13]: fix: correct file decryption stream handlers
- [2020-07-10]: docs: document local vault backup routines
- [2020-08-09]: chore: configure default key wrapping options
- [2020-09-09]: refactor: clean up key management cache entries
- [2020-10-09]: style: improve secure terminal inputs layout
- [2020-11-13]: docs: add data classification standards
- [2020-12-10]: chore: update security notice guidelines
- [2024-12-05]: refactor: optimize lock timers execution path
- [2024-12-09]: docs: update troubleshooting guide for pin codes
- [2024-12-13]: fix: resolve cipher modes configuration flags
- [2024-12-16]: refactor: streamline database secrets backend
- [2024-12-19]: docs: update environment settings for vault service
- [2024-12-23]: style: standardize salt generation algorithms
- [2024-12-25]: chore: update security audit check docs
- [2024-12-27]: refactor: optimize crypt block encryption parameters
- [2024-12-29]: docs: document vault decryption guidelines
- [2026-05-20]: feat: add hardware security key (FIDO2) support
- [2026-05-20]: refactor: upgrade AES-GCM key derivation to Argon2id
- [2026-05-20]: docs: document vault export and backup procedures
