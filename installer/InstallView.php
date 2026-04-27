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
        $https   = !empty($_POST['https_enabled']) ? 'true' : 'false';

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

        // Step 2: Ensure the target database is empty to avoid half-installs.
        $existing = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        if (!empty($existing)) {
            self::abort(
                'データベースにテーブルが既に存在します',
                'データベース「' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '」には既にテーブルがあります。'
                . '空のデータベースを用意してから再実行してください。',
            );
        }

        // Step 3: Write .env with the verified credentials.
        $envPath    = dirname(__DIR__) . '/.env';
        $envContent = implode("\n", [
            "DB_DSN={$dsn}",
            "DB_USER={$user}",
            "DB_PASSWORD={$pass}",
            "APP_HTTPS={$https}",
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
