# Changelog

All notable changes to `datalab-sdk-laravel` will be documented in this file.

## Unreleased

- Added the `DatalabParseQuality` enum; `ConvertResultResponse` now exposes `parseQuality` (Excellent/Good/Fair/Poor with `recommendedAction()`) alongside the raw `parseQualityScore`.
- Added the Convert API (`Datalab::convert()`) with `ConvertRequest`, `ConvertResponse`/`ConvertResultResponse` DTOs, and the `DatalabExtra` enum. Supports the new `include_markdown_in_chunks`, `word_bboxes`, `fence_synthetic_captions`, `token_efficient_markdown`, `processing_location`, and `eval_rubric_id` options.
- Deprecated the Marker API (`Datalab::marker()`, `MarkerRequest`, and the marker DTOs) — Datalab is replacing it with the Convert API.
- Added Laravel 13 support (`illuminate/http` and `illuminate/contracts` now allow `^13.0`; CI matrix extended with Laravel 13 / Testbench 11).
- Removed dead code in `MarkerResultResponse` pagination parsing flagged by PHPStan.

## Initial Release - 2026-02-14

This is the initial release of the Datalab SDK for Laravel, created by [ImmiTranslate](https://immitranslate.com).
