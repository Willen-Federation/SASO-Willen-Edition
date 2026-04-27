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

        $portPart = ($port !== '' && $port !== null) ? ";port={$port}" : '';
        $dsn = "mysql:host={$host}{$portPart};dbname={$name};charset={$charset}";

        // Test the connection before writing anything to disk.
        try {
            $pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_CLASS,
            ]);
        } catch (\PDOException $e) {
            $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            echo "<!DOCTYPE html><html lang='ja'><head><meta charset='UTF-8'><title>接続エラー</title></head><body>";
            echo "<h2>データベース接続エラー</h2>";
            echo "<p>{$msg}</p>";
            echo "<p><a href='javascript:history.back()'>戻る</a></p>";
            echo "</body></html>";
            exit;
        }

        // Write .env with the validated credentials.
        $envPath = dirname(__DIR__) . '/.env';
        $envContent = implode("\n", [
            "DB_DSN={$dsn}",
            "DB_USER={$user}",
            "DB_PASSWORD={$pass}",
            "APP_HTTPS={$https}",
            '',
        ]);
        if (file_put_contents($envPath, $envContent) === false) {
            echo "<!DOCTYPE html><html lang='ja'><head><meta charset='UTF-8'><title>書込みエラー</title></head><body>";
            echo "<h2>.env ファイルの書込みに失敗しました</h2>";
            echo "<p>サーバの書込み権限を確認してください。</p>";
            echo "<p><a href='javascript:history.back()'>戻る</a></p>";
            echo "</body></html>";
            exit;
        }

        require_once 'installer/createTables.php';
        util\Redirect::redirect('installer/installed');
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
