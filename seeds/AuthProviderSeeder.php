<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AuthProviderSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $data = [
            [
                'name'      => 'Local Login',
                'type'      => 'local',
                'enabled'   => 1,
                'is_default'=> 1,
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ],
            [
                'name'      => 'Auth0',
                'type'      => 'oidc',
                'issuer_or_metadata_url' => 'https://example.auth0.com/.well-known/openid-configuration',
                'client_id' => 'placeholder-client-id',
                'enabled'   => 0,
                'is_default'=> 0,
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ],
            [
                'name'      => 'SAML IdP',
                'type'      => 'saml',
                'issuer_or_metadata_url' => 'https://example.com/simplesaml/saml2/idp/metadata.php',
                'enabled'   => 0,
                'is_default'=> 0,
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ],
        ];

        $table = $this->table('auth_provider');
        foreach ($data as $row) {
            $exists = $this->fetchRow(sprintf("SELECT id FROM auth_provider WHERE type = '%s' AND name = '%s'", $row['type'], $row['name']));
            if (!$exists) {
                $table->insert($row)->saveData();
            }
        }
    }
}
