<!--
  Thank you for contributing to SASO — Willen Edition!
  English and Japanese are both welcome. 日本語・英語どちらでも構いません。
  Please fill in the sections below; remove any that don't apply.
-->

## Summary / 概要

<!-- Why is this change needed? What problem does it solve? -->
<!-- このPRが必要な理由・解決する課題を簡潔に書いてください。 -->

## Changes / 変更点

<!-- Bullet list of notable changes. Reference architectural decisions (ADRs) if relevant. -->
- 

## Type of change / 変更種別

- [ ] `feat` — new feature / 新機能
- [ ] `fix` — bug fix / バグ修正
- [ ] `security` — security fix / セキュリティ修正
- [ ] `refactor` — non-behavior change / 振る舞いを変えないリファクタリング
- [ ] `perf` — performance / パフォーマンス改善
- [ ] `docs` — documentation only / ドキュメントのみ
- [ ] `test` — tests only / テストのみ
- [ ] `chore` / `ci` — tooling / build / CI

## Related issues / 関連 Issue

<!-- e.g. Closes #123, Refs #456 -->
- 

## How to test / 動作確認手順

<!-- Step-by-step instructions a reviewer can follow. Include URLs, fixtures, env vars. -->
1. 
2. 
3. 

## Screenshots / 画面キャプチャ (if UI changes)

<!-- Drag and drop images here. -->

## Impact / 影響範囲

- **Breaking change?** / 破壊的変更があるか: <!-- yes / no — if yes, describe the migration -->
- **Database migration?** / DB マイグレーションが必要か: <!-- yes / no -->
- **Configuration change?** / 設定変更が必要か: <!-- yes / no — list new env vars / config keys -->
- **i18n strings touched?** / i18n 文言の追加・変更: <!-- yes / no -->

## Checklist / チェックリスト

- [ ] My branch follows GitHub Flow and is up-to-date with `main`
- [ ] Commits follow [Conventional Commits](https://www.conventionalcommits.org/)
- [ ] CI passes (`php -l`, PHPStan, PHPUnit when configured)
- [ ] New code has unit / integration tests where applicable
- [ ] User-visible strings use the i18n helper (post-M3)
- [ ] `CHANGELOG.md` has an entry under `## [Unreleased]`
- [ ] No `.env`, `vendor/`, secrets, or generated files committed
- [ ] PR description explains the **why**, not just the **what**
- [ ] PR title is in Conventional Commits format (e.g. `feat(auth): ...`)
