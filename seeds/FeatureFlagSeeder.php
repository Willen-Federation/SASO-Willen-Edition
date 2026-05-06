<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class FeatureFlagSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'key_name'      => 'auth.oidc.discovery_cache',
                'description'   => 'Enable caching of OIDC discovery documents',
                'enabled'       => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'key_name'      => 'item.new_layout',
                'description'   => 'Use the new Tailwind-based layout for item details',
                'enabled'       => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ];

        $table = $this->table('feature_flag');
        foreach ($data as $row) {
            $exists = $this->fetchRow(sprintf("SELECT id FROM feature_flag WHERE key_name = '%s'", $row['key_name']));
            if (!$exists) {
                $table->insert($row)->saveData();
            }
        }
    }
}
