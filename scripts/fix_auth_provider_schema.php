<?php
declare(strict_types=1);

/**
 * One-shot maintenance script that brings the `auth_provider` table up to the
 * schema the application code expects.
 *
 * Some production installs created the table from an older script (pre-M4-D
 * migration) and ended up missing columns the wizard at `/auth/provider/new`
 * tries to INSERT into. Running this script is safe to repeat: it inspects
 * the live columns first and only issues `ALTER TABLE ADD COLUMN` for the
 * ones that are actually absent.
 *
 * Usage from the project root:
 *
 *     php scripts/fix_auth_provider_schema.php
 *
 * No flags, no side effects beyond column additions. Existing data and
 * existing columns are left alone.
 */

require_once __DIR__ . '/../ConfigLoader.php';
require_once __DIR__ . '/../ClassLoader.php';

use saso\ConfigLoader;
use saso\ClassLoader;

$config = ConfigLoader::load(__DIR__ . '/../');
spl_autoload_register(ClassLoader::load($config));

$db = $config['database'] ?? [];
if (empty($db['dsn'])) {
    fwrite(STDERR, "ERROR: database.dsn is not configured.\n");
    exit(1);
}

try {
    $pdo = new PDO($db['dsn'], $db['user'] ?? null, $db['password'] ?? null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, 'ERROR: cannot connect to database: ' . $e->getMessage() . "\n");
    exit(1);
}

// Confirm the table exists at all before trying to ALTER it.
$tableStmt = $pdo->query("SHOW TABLES LIKE 'auth_provider'");
$tableRow  = $tableStmt instanceof PDOStatement ? $tableStmt->fetchColumn() : false;
if ($tableRow === false) {
    fwrite(STDERR, "ERROR: table `auth_provider` does not exist. Run the M4 migration (or recreate it) first.\n");
    exit(1);
}

// Snapshot the current column list so we can decide what to add.
$descStmt = $pdo->query('DESCRIBE auth_provider');
$rows     = $descStmt instanceof PDOStatement ? $descStmt->fetchAll(PDO::FETCH_ASSOC) : [];
$existing = [];
foreach ($rows as $row) {
    $existing[strtolower((string) $row['Field'])] = true;
}

echo "Current columns: " . implode(', ', array_keys($existing)) . "\n\n";

/**
 * Canonical column definitions, mirroring migrations/M4/20260426120002_create_auth_provider.php.
 *
 * Each entry produces an `ALTER TABLE ... ADD COLUMN ... AFTER <previous>` so
 * the resulting layout matches a fresh install, regardless of insertion order.
 *
 * @var list<array{name:string, ddl:string, after:?string}>
 */
$canonical = [
    [
        'name'  => 'name',
        'ddl'   => "`name` VARCHAR(100) NOT NULL COMMENT 'Display name shown on the login button.'",
        'after' => 'id',
    ],
    [
        'name'  => 'type',
        'ddl'   => "`type` ENUM('local','oidc','saml') NOT NULL COMMENT 'Discriminator — drives which AuthProvider implementation is constructed.'",
        'after' => 'name',
    ],
    [
        'name'  => 'issuer_or_metadata_url',
        'ddl'   => "`issuer_or_metadata_url` VARCHAR(500) NULL COMMENT 'OIDC discovery URL (.well-known/openid-configuration) or SAML metadata URL.'",
        'after' => 'type',
    ],
    [
        'name'  => 'client_id',
        'ddl'   => "`client_id` VARCHAR(255) NULL",
        'after' => 'issuer_or_metadata_url',
    ],
    [
        'name'  => 'client_secret_encrypted',
        'ddl'   => "`client_secret_encrypted` BLOB NULL COMMENT 'AES-256-GCM ciphertext from SecretEncryptor; APP_KEY is the wrapping key.'",
        'after' => 'client_id',
    ],
    [
        'name'  => 'scopes',
        'ddl'   => "`scopes` VARCHAR(500) NULL COMMENT 'Space-separated scope list for OIDC; ignored for SAML.'",
        'after' => 'client_secret_encrypted',
    ],
    [
        'name'  => 'claim_mapping',
        'ddl'   => "`claim_mapping` JSON NULL COMMENT 'Operator override for IdP claim names (cf. Saso\\\\Domain\\\\Auth\\\\ClaimMapping).'",
        'after' => 'scopes',
    ],
    [
        'name'  => 'enabled',
        'ddl'   => "`enabled` TINYINT(1) NOT NULL DEFAULT 0",
        'after' => 'claim_mapping',
    ],
    [
        'name'  => 'is_default',
        'ddl'   => "`is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = featured first on the login screen.'",
        'after' => 'enabled',
    ],
    [
        'name'  => 'created_at',
        'ddl'   => "`created_at` DATETIME NOT NULL",
        'after' => 'is_default',
    ],
    [
        'name'  => 'updated_at',
        'ddl'   => "`updated_at` DATETIME NOT NULL",
        'after' => 'created_at',
    ],
];

$added = [];
foreach ($canonical as $col) {
    if (isset($existing[strtolower($col['name'])])) {
        continue;
    }
    $after = $col['after'] !== null ? " AFTER `{$col['after']}`" : '';
    $sql   = "ALTER TABLE `auth_provider` ADD COLUMN {$col['ddl']}{$after}";
    echo "+ Adding `{$col['name']}` …\n";
    try {
        $pdo->exec($sql);
        $added[] = $col['name'];
        $existing[strtolower($col['name'])] = true;
    } catch (PDOException $e) {
        fwrite(STDERR, "  FAILED: " . $e->getMessage() . "\n");
        fwrite(STDERR, "  SQL was: $sql\n");
        exit(1);
    }
}

if ($added === []) {
    echo "Nothing to do — auth_provider already has every expected column.\n";
} else {
    echo "\nAdded " . count($added) . " column(s): " . implode(', ', $added) . "\n";
}
