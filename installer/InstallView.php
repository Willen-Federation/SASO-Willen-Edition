<?php
namespace saso\installer;

use saso\entity\Member;
use saso\framework\Setter;
use saso\framework\View;
use saso\util;

final class InstallView implements View
{
    use Setter;
    private Member $member;

    public function display(): void
    {
        $host    = $_POST['db_host'] ?? 'localhost';
        $port    = $_POST['db_port'] ?? '';
        $name    = $_POST['db_name'] ?? '';
        $user    = $_POST['db_user'] ?? '';
        $pass    = $_POST['db_password'] ?? '';
        $charset = in_array($_POST['db_charset'] ?? '', ['utf8mb4', 'utf8'], true)
            ? $_POST['db_charset']
            : 'utf8mb4';
        $https         = !empty($_POST['https_enabled']) ? 'true' : 'false';
        $confirmReset  = !empty($_POST['confirm_reset']);

        $portPart = ($port !== '') ? ";port={$port}" : '';
        $dsn = "mysql:host={$host}{$portPart};dbname={$name};charset={$charset}";

        // Step 1: Test the DB connection before writing anything.
        try {
            $pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_CLASS,
            ]);
        } catch (\PDOException $e) {
            self::abort(
                'データベース接続エラー',
                htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
            );
        }

        // Step 2: Check for existing tables.
        $existing = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        if (!empty($existing)) {
            if (!$confirmReset) {
                // Ask the user whether to drop all existing tables.
                self::confirmReset($existing, $_POST);
            }
            // User confirmed — drop everything and start fresh.
            self::dropAllTables($pdo, $existing);
        }

        // Step 3: Write .env with the verified credentials.
        // Preserve APP_KEY if it already exists (re-install must not rotate the key).
        $envPath = dirname(__DIR__) . '/.env';
        $existingEnv = \saso\util\EnvLoader::loadFile($envPath);
        $appKey  = \saso\util\EnvLoader::get($existingEnv, 'APP_KEY')
            ?? base64_encode(\Saso\Infrastructure\Auth\Crypto\SecretEncryptor::generateKey());
        $envContent = implode("\n", [
            "DB_DSN={$dsn}",
            "DB_USER={$user}",
            "DB_PASSWORD={$pass}",
            "APP_HTTPS={$https}",
            "APP_KEY={$appKey}",
            '',
        ]);
        if (file_put_contents($envPath, $envContent) === false) {
            self::abort(
                '.env ファイルの書込みに失敗しました',
                'サーバの書込み権限を確認してください。',
            );
        }

        // Step 4: Create tables and admin account.
        // $pdo and $tableCharset are visible to the included file's scope.
        $tableCharset = $charset;
        require_once 'installer/createTables.php';

        util\Redirect::redirect('installer/installed');
    }

    /**
     * Render a confirmation page listing the tables to be dropped.
     * All form values are forwarded as hidden fields so the user does not
     * need to re-enter them. Never returns.
     */
    private static function confirmReset(array $tables, array $post): never
    {
        $tableRows = implode('', array_map(
            fn($t) => '<li><code>' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</code></li>',
            $tables,
        ));

        // Build hidden fields that replay every submitted value.
        $hidden = '';
        $replay = [
            'db_host', 'db_port', 'db_name', 'db_user', 'db_password',
            'db_charset', 'https_enabled', 'name', 'id', 'password', 'password_confirm',
        ];
        foreach ($replay as $key) {
            if (isset($post[$key])) {
                $k = htmlspecialchars($key,        ENT_QUOTES, 'UTF-8');
                $v = htmlspecialchars($post[$key], ENT_QUOTES, 'UTF-8');
                $hidden .= "<input type='hidden' name='{$k}' value='{$v}'>\n";
            }
        }

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="ja">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <title>データベースの初期化確認</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
        <div class="container py-5" style="max-width:640px">
          <div class="card border-danger">
            <div class="card-header bg-danger text-white fw-bold">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>データベースの初期化確認
            </div>
            <div class="card-body">
              <p>データベースに以下のテーブルが既に存在します。<br>
              インストールを続けると、<strong>これらのテーブルとデータがすべて削除</strong>されます。</p>
              <ul class="mb-3">{$tableRows}</ul>
              <p class="text-danger fw-bold">この操作は取り消せません。本当によろしいですか？</p>
              <form method="post" action="./installer/install/">
                {$hidden}
                <input type="hidden" name="confirm_reset" value="1">
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-danger">初期化してインストール</button>
                  <a href="javascript:history.back()" class="btn btn-secondary">戻る</a>
                </div>
              </form>
            </div>
          </div>
        </div>
        </body>
        </html>
        HTML;
        exit;
    }

    /** Drop every table in the given list, disabling FK checks first. */
    private static function dropAllTables(\PDO $pdo, array $tables): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '', $table) . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /** Emit a minimal error page and halt. Never returns. */
    private static function abort(string $heading, string $detail): never
    {
        $h = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
        echo "<!DOCTYPE html><html lang='ja'><head><meta charset='UTF-8'>"
           . "<title>{$h}</title></head><body>"
           . "<h2>{$h}</h2><p>{$detail}</p>"
           . "<p><a href='javascript:history.back()'>戻る</a></p>"
           . "</body></html>";
        exit;
    }

    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return '';
    }
    public function getContent(): \Closure
    {
        return fn()=>null;
    }
}
