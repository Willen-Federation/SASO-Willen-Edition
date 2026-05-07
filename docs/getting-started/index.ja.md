# はじめに

SASO のインスタンスを立ち上げる経路は 3 種類あります：

1. **Docker** — 開発・評価に推奨。本番想定の mod_php + Apache + MariaDB 構成と同等。
2. **標準的な PHP ホスト** — 自分で管理する Linux 機（VPS、専用、PHP 8.2+ を実行できる PaaS）
3. **レンタル・共有ホスティング** — SSH と Composer が使えない Apache + .htaccess 環境。`vendor/` を同梱したリリース ZIP（M5 で導入予定）を使用。

インストール後、アプリのスキーマは `installer/start` から対話形式で作成されます。詳細は [インストール](installation.md) を参照。

## 動作要件

| コンポーネント | バージョン |
|---|---|
| PHP | 8.2 以上 |
| MariaDB / MySQL | 10.6 以上 / 8.0 以上 |
| Web サーバー | Apache 2.4 以上 + `mod_rewrite`（LiteSpeed 互換可） |
| 必須 PHP 拡張 | `pdo_mysql`, `gd`, `zip`, `intl`, `mbstring`, `opcache`, `fileinfo` |

## Docker (Colima / Docker Desktop)

```bash
colima start --cpu 4 --memory 4 --disk 20   # macOS のみ。Linux は不要
make up                                      # イメージビルド + app, db, adminer 起動
make install                                 # コンテナ内で composer install
make migrate                                 # migrations/*.sql を適用
open http://localhost:8080/installer/start
```

Adminer は `http://localhost:8081`（server: `db` / user: `saso_user` / password: `saso_dev_password`）。

OIDC / SAML テスト用 IdP を追加する場合：

```bash
make up-sso       # Keycloak が http://localhost:8082 (admin / admin)
```

## 標準的な PHP ホスト

```bash
git clone https://github.com/Willen-Federation/SASO-Willen-Edition.git
cd SASO-Willen-Edition
composer install --no-dev --optimize-autoloader

cp .env.example .env
$EDITOR .env             # DB_DSN / DB_USER / DB_PASSWORD / APP_HTTPS を設定

$EDITOR .htaccess        # サブディレクトリ配置時は RewriteBase を調整

# その後、ブラウザで /installer/start にアクセス
```

## レンタル・共有ホスティング

SSH や Composer が利用できないホストの場合：

1. [GitHub Releases](https://github.com/Willen-Federation/SASO-Willen-Edition/releases) から最新リリース ZIP を入手（`vendor/` 同梱済み）
2. cPanel / FTP で対象ディレクトリへ展開アップロード
3. `.htaccess` の `RewriteBase` を設置パスに合わせて編集
4. `<ドメイン>/installer/start` にアクセスしてウィザードを完了
5. ウィザードは `installer/installer.json` を生成し、以降のアクセスをロック

> リリース ZIP は **M5** で配布開始予定です。それまでは開発機で `composer install --no-dev` 後に手動 zip 化してください。

## 次のステップ

- [インストール手順](installation.md)
- [設定リファレンス](configuration.md)
- [セキュリティポリシー](../security.md)
