# インストール

本ページでは `/installer/start` の **Web インストーラ** を扱います。インストーラはアプリのスキーマと初期管理者アカウントを作成し、`installer/installer.json` を作成して以降の起動をロックします。

## 事前準備

- **空の MariaDB / MySQL データベース**（`CREATE`, `INSERT`, `SELECT`, `UPDATE`, `DELETE`, `ALTER` 権限を持つユーザー）
- リポジトリ（またはリリース ZIP）を DocumentRoot 配下に配置済み
- `.env` に `DB_DSN`, `DB_USER`, `DB_PASSWORD` を設定済み（推奨）。または同等の値を `config.json` の `database.*` に書く
- `.htaccess` の `RewriteBase` を設置パスに合わせて調整

## 手順

1. ブラウザで **`/installer/start`** を開きます。環境チェック（PHP バージョン、必須拡張）が実行されます。
2. **DB 接続確認。** 入力された DSN に接続を試行します。失敗時は PDO のエラー詳細が表示されるので資格情報や権限を修正してください。
3. **スキーマ作成。** `installer/createTables.php` がテーブルを作成します。既存テーブルは破棄されません — 再実行は安全に中断します。
4. **初期管理者作成。** 表示名、ログイン ID、パスワードを入力。
   - ログイン ID: 8〜20 文字、`[0-9a-zA-Z_-]`
   - パスワード: 8〜20 文字の英数字のみ
   - パスワードは `password_hash(PASSWORD_ARGON2ID)` で保存（M1）
5. **インストーラロック。** 完了時に `installer/installer.json` が生成され、以降の `/installer/*` アクセスはログイン画面にリダイレクトされます。

## インストール後

- `/auth/login` でブートストラップ管理者としてログイン
- 追加メンバーは管理画面から（UI は M4 で導入予定。それまでは Adminer / SQL シェルで `Member` 行を管理）
- [セキュリティポリシー](../security.md) のハードニング推奨を適用：
  - TLS を Web サーバー層で有効にした後、`.env` に `APP_HTTPS=true` を設定
  - ファイル権限制限：`.env` と `config.json` は `0640`

## トラブルシューティング

| 症状 | 原因の例 |
|---|---|
| DSN テストで `No such file or directory` | host / unix socket パス誤り。Docker compose では `host=db` |
| `Access denied for user` | DB 権限不足。`GRANT ALL ON saso_db.* TO 'saso_user'@'%';` を実行 |
| インストーラが即ログインへリダイレクト | `installer/installer.json` が既存。削除して再実行 |
| 任意のインストーラページ前に 500 エラー | `.htaccess` が読まれていない。`AllowOverride All` を確認（Docker 開発イメージは既設定） |
| M1 アップグレード後に `Argon2id digest truncated` | [`migrations/M1_001_widen_password_column.sql`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/migrations/M1_001_widen_password_column.sql) を適用 |

## 次のステップ

- [設定](configuration.md) — 環境変数と実行時調整
- [アーキテクチャ概要](../architecture/index.md) — コードベース構成
