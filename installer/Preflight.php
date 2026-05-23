<?php

declare(strict_types=1);

namespace saso\installer;

/**
 * Filesystem + PHP capability checks that must pass before the installer
 * wizard is allowed to mutate `.env`.
 *
 * The previous wizard implementation only ran a "checks" panel inside the
 * Start step that the operator could click past even when `.env` was not
 * writable, producing confusing partial writes downstream. This class is the
 * authoritative gate: {@see StartView} (and any other step that takes a
 * mutation) consults `isOk()` first and redirects to the dedicated
 * preflight-failed page when anything fails.
 */
final class Preflight
{
    /** @var list<PreflightCheck> */
    private array $checks;

    /** @param list<PreflightCheck> $checks */
    private function __construct(array $checks)
    {
        $this->checks = $checks;
    }

    /**
     * Run every check against the given .env path. Production callers use
     * {@see WizardState::envPath()}; tests inject a tmpdir.
     */
    public static function run(string $envPath): self
    {
        $checks = [];

        // 1. random_bytes() — guaranteed on PHP ≥ 7.0 but checked explicitly
        // so a stripped-down PHP build (e.g. some embedded distros) fails
        // loudly here instead of producing a zero-byte secret later.
        $checks[] = new PreflightCheck(
            id: 'random_bytes',
            label: 'PHP に random_bytes() が存在する',
            ok: function_exists('random_bytes'),
            detail: function_exists('random_bytes')
                ? '利用可能です'
                : 'PHP のビルドで CSPRNG が無効になっています。php-cli / php-fpm を再ビルドしてください。',
            remedy: null,
        );

        // 2. .env parent directory exists. Without this we have nowhere to
        // write the file.
        $envDir       = dirname($envPath);
        $dirExists    = is_dir($envDir);
        $checks[] = new PreflightCheck(
            id: 'env_dir_exists',
            label: '`.env` の親ディレクトリが存在する',
            ok: $dirExists,
            detail: $dirExists
                ? $envDir.' が存在します'
                : $envDir.' が見つかりません',
            remedy: $dirExists ? null : sprintf('mkdir -p %s', escapeshellarg($envDir)),
        );

        // 3. .env parent directory writable by the running PHP user. Required
        // for both first-run (create file) and rotation (rename temp file).
        $dirWritable  = $dirExists && is_writable($envDir);
        $owner        = $dirExists ? self::ownerName($envDir) : '?';
        $phpUser      = self::currentPhpUser();
        $checks[] = new PreflightCheck(
            id: 'env_dir_writable',
            label: '`.env` の親ディレクトリに書き込み可能',
            ok: $dirWritable,
            detail: $dirWritable
                ? $envDir.' は書き込み可能です ('.$phpUser.')'
                : sprintf(
                    '%s に PHP プロセス (%s) から書き込めません。所有者: %s',
                    $envDir,
                    $phpUser,
                    $owner,
                ),
            remedy: $dirWritable
                ? null
                : self::remedyForDir($envDir, $phpUser),
        );

        // 4. If .env exists, it must be writable (atomic rename only works
        // when we own the file or have write permission to it).
        $envExists    = is_file($envPath);
        $envWritable  = !$envExists || is_writable($envPath);
        $checks[] = new PreflightCheck(
            id: 'env_file_writable',
            label: '`.env` が書き込み可能 (存在する場合)',
            ok: $envWritable,
            detail: !$envExists
                ? '.env はまだ存在しません — インストーラが作成します。'
                : ($envWritable
                    ? $envPath.' は書き込み可能です'
                    : $envPath.' に書き込めません'),
            remedy: $envWritable
                ? null
                : self::remedyForFile($envPath, $phpUser),
        );

        return new self($checks);
    }

    public function isOk(): bool
    {
        foreach ($this->checks as $check) {
            if (!$check->ok) {
                return false;
            }
        }
        return true;
    }

    /** @return list<PreflightCheck> */
    public function checks(): array
    {
        return $this->checks;
    }

    /** @return list<PreflightCheck> */
    public function failures(): array
    {
        return array_values(array_filter($this->checks, static fn (PreflightCheck $c): bool => !$c->ok));
    }

    private static function currentPhpUser(): string
    {
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $info = @posix_getpwuid((int) posix_geteuid());
            if (is_array($info)) {
                return (string) $info['name'];
            }
        }
        $env = getenv('USER');
        return $env !== false && $env !== '' ? $env : 'php';
    }

    private static function ownerName(string $path): string
    {
        $uid = @fileowner($path);
        if ($uid === false) {
            return '?';
        }
        if (function_exists('posix_getpwuid')) {
            $info = @posix_getpwuid((int) $uid);
            if (is_array($info)) {
                return (string) $info['name'];
            }
        }
        return (string) $uid;
    }

    private static function remedyForDir(string $dir, string $phpUser): string
    {
        return sprintf(
            "sudo chown %s %s\nsudo chmod u+w %s",
            escapeshellarg($phpUser),
            escapeshellarg($dir),
            escapeshellarg($dir),
        );
    }

    private static function remedyForFile(string $path, string $phpUser): string
    {
        return sprintf(
            "sudo chown %s %s\nsudo chmod u+w %s",
            escapeshellarg($phpUser),
            escapeshellarg($path),
            escapeshellarg($path),
        );
    }
}
