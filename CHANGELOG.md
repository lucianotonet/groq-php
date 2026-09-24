# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- **Vision default model:** `Vision` now defaults to `qwen/qwen3.8-27b` (successor of `qwen/qwen3.6-27b`, shutdown 2026-09-14 for free/developer tiers). Calling `vision()->analyze()` without an explicit model no longer hits a retired ID.
- **Built-in tools example:** replaced `compound-beta` + `compound_custom` with GPT-OSS `tools: [{type: browser_search}, ...]` (Compound systems decommissioned; see Groq deprecations).

### Changed
- `BuiltInTools`: added `BROWSER_SEARCH` and `tools()` helper for the current GPT-OSS built-in tools API; marked Compound-oriented helpers/constants as deprecated in PHPDoc.
- Reasoning examples/tests and README references updated from `qwen/qwen3.6-27b` to `qwen/qwen3.8-27b`.

## [v1.4.2] - 2026-08-25

### Added
- **Documents (RAG) & Citations restored:** re-added the `documents` and `citation_options` Chat Completions parameters as pass-through forwarding, plus `BuiltInTools::document()` and `BuiltInTools::documentFromFile()` helpers. These are valid API parameters; support is model-specific (the legacy RAG models `llama-3.3-70b-versatile` / `llama-3.1-8b-instant` were retired on 2026-08-16 for free/developer tiers, so current models reject `documents` with `not supported with this model`). The library forwards them as-is and lets the API enforce model support.
- **Chat Completions parity:** added the `disable_tool_validation` parameter pass-through.
- **Audio transcription/translation parity:** added the `url` parameter (alternative to `file`) and fixed `timestamp_granularities[]` so each granularity is sent as a repeated multipart field (previously sent as a single array, which the API rejected). Both endpoints now accept either `file` or `url`.

## [v1.4.1] - 2026-08-25

### Fixed
- **Built-in Tools / Compound:** use the current Compound system models (`compound-beta` / `compound-beta-mini`) instead of the deprecated `groq/compound` alias.
- Removed the `documents` (RAG) and `citation_options` Chat Completions parameters: the live Groq API rejects them on all current models, so they were non-functional. `compound_custom` and `search_settings` remain supported.
- New tests (`BuiltInToolsTest`, `ResponsesTest`) are now mode-aware so they pass both against the offline mock (default) and the real API (`GROQ_LIVE_TESTS=1`).

## [v1.4.0] - 2026-08-25

### Added
- Responses API client (`Groq::responses()`): OpenAI-compatible `input`/`output` model responses with streaming, structured outputs (`text.format`), and reasoning controls.
- Built-in (server-side) tools & Compound support: `LucianoTonet\GroqPHP\BuiltInTools` helpers for `compound_custom`, plus `documents`, `search_settings`, and `citation_options` on Chat Completions (RAG & citations).
- `Speech::sampleRate()` and `Speech::speed()` fluent setters forwarded to the TTS API.
- Forwarding of `timestamp_granularities[]` in Transcriptions (Groq supports it with `response_format=verbose_json`).
- Documentation: Prompt Caching & Content Moderation (safeguard models).
- Offline mock test layer (`tests/MockRouter`) so the suite runs without network or API credits; CI runs mocked by default with a nightly live job.

### Changed
- Documentation refreshed and verified against `console.groq.com/docs` (model defaults, Vision, Reasoning, deprecated models, features).

### Fixed
- `Transcriptions` docblock incorrectly claimed `timestamp_granularities[]` was unsupported.
