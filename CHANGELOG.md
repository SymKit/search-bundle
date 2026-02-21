# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `PreSearchEvent` and `PostSearchEvent` for extensible search lifecycle hooks.
- `EngineNotFoundException` dedicated exception for unknown engine lookups.
- `default_engine` configuration option to explicitly set the default search engine.
- ARIA attributes on the GlobalSearch modal (dialog role, listbox, live region).
- Unit tests for `GlobalSearch`, `AsSearchProvider`, `SearchProviderPass`, `prependExtension`.
- Integration test for full search flow (provider to results).
- Integration test for `default_engine` configuration.

### Changed

- `SearchService` now dispatches `PreSearchEvent` / `PostSearchEvent` when an event dispatcher is available.
- `SearchEngineRegistry` throws `EngineNotFoundException` instead of plain `InvalidArgumentException`.

## [0.0.1] - 2025-01-01

### Added

- Initial release with multi-engine search architecture.
- `SearchProviderInterface` contract and `#[AsSearchProvider]` attribute.
- `SearchService` with generator-based result streaming.
- `SearchEngineRegistry` with `ServiceLocator` for lazy engine loading.
- `GlobalSearch` Symfony UX Live Component with Stimulus keyboard navigation.
- Tailwind CSS-based search modal UI with dark mode support.
- XLIFF translations (English, French).
- PHPStan level 9, Deptrac, Infection, PHP-CS-Fixer, GrumPHP configuration.
- GitHub Actions CI with PHP 8.2-8.4 and Symfony 7/8 matrix.
