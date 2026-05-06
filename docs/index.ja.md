# SASO — Willen Edition

**PHP 製のオープンソース在庫・倉庫管理システム**。商品・分類管理（ネスト集合モデル）、バーコード、TCPDF によるラベル印刷、棚管理を提供します。一般的なレンタルサーバーでも、Docker でも動作します。

本ドキュメントサイトでは、アプリの運用方法、貢献方法、近代化ロードマップ（M0〜M5）を導くアーキテクチャ判断を扱います。

> <a href="/">English</a> / オリジナル日本語 README は [`ORIGINAL_README.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/ORIGINAL_README.md) にあります。

## 章立て

| セクション | 対象読者 |
|---|---|
| [はじめに](getting-started/index.md) | 初回セットアップを行う運用者 |
| [設定](getting-started/configuration.md) | `.env` / `config.json` / システム設定を調整する運用者 |
| [アーキテクチャ](architecture/index.md) | Clean Architecture / DDD 構成を把握したい貢献者 |
| [開発](development/index.md) | コード・テスト・ドキュメントに貢献する人 |
| [セキュリティ](security.md) | 脆弱性報告手順と運用ハードニング |
| [API リファレンス](api.md) | `/api/v1/*` を利用する API クライアント（M3 で導入） |
| [エラーコード](error-codes.md) | `SASO-*` エラーコードを追跡する人 |

## 現状

本フォークは現在近代化の途中です。執筆時点で **M0（Stabilize）**、**M1（Security Hotfix）**、および **M2（Tooling & Composer）** の大半が完了しています。最新状況は [変更履歴](changelog.md) を参照してください。

## ライセンス

日本標準機構および Willen Federation 貢献者によるコードは [GNU GPL v3](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/LICENSE) です。`extention/` 以下のサードパーティ製ライブラリは各自のライセンスに従います（TCPDF: LGPLv3 等）。
