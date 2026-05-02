<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../ClassLoader.php';

use Saso\Infrastructure\Setting\PdoSystemSettingService;
use Saso\Domain\Setting\SettingValue;
use saso\repository\DBConnection;

$configPath = __DIR__ . '/../config.json';
if (!file_exists($configPath)) {
    echo "No config.json found at $configPath\n";
    exit(1);
}

$config = json_decode(file_get_contents($configPath), true);
if (!is_array($config)) {
    echo "Failed to parse config.json\n";
    exit(1);
}

// Ensure DB connects
try {
    $pdo = DBConnection::getPdo();
} catch (\Throwable $e) {
    echo "Failed to connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

$settingService = new PdoSystemSettingService($pdo);

$migrationMap = [
    'default_locale' => ['type' => 'string', 'default' => 'en'],
    'mail' => [
        'smtp_host' => ['type' => 'string', 'default' => ''],
        'smtp_port' => ['type' => 'int', 'default' => 25],
    ],
    'outputRow' => ['type' => 'int', 'default' => 2],
    'sheetAmount' => ['type' => 'int', 'default' => 10],
    'auth' => [
        'mode' => ['type' => 'string', 'default' => 'local'],
    ],
];

echo "Migrating config.json to system_setting...\n";

function migrateValue(string $key, array $def, mixed $val, PdoSystemSettingService $settingService): void {
    if ($val === null) {
        $val = $def['default'];
    }
    
    try {
        // Only set if not already present
        $existing = $settingService->get($key);
        if ($existing === null) {
            if ($def['type'] === 'int') {
                $settingService->set($key, SettingValue::int((int)$val), 'installer', 'Migrated from config.json');
            } elseif ($def['type'] === 'string') {
                $settingService->set($key, SettingValue::string((string)$val), 'installer', 'Migrated from config.json');
            }
            echo "Migrated $key\n";
        } else {
            echo "Skipped $key (already exists)\n";
        }
    } catch (\Throwable $e) {
        echo "Error migrating $key: " . $e->getMessage() . "\n";
    }
}

foreach ($migrationMap as $key => $def) {
    if (isset($def['type'])) {
        // Flat key
        $val = $config[$key] ?? null;
        migrateValue($key, $def, $val, $settingService);
    } else {
        // Nested key
        foreach ($def as $subKey => $subDef) {
            $val = $config[$key][$subKey] ?? null;
            $fullKey = "{$key}.{$subKey}";
            migrateValue($fullKey, $subDef, $val, $settingService);
        }
    }
}

echo "Migration complete.\n";
