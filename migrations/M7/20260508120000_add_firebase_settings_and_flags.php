<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds Firebase settings and Feature Flags to the system.
 */
final class AddFirebaseSettingsAndFlags extends AbstractMigration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        
        // Add Firebase settings to system_setting
        $this->table('system_setting')
            ->insert([
                [
                    'key' => 'firebase.api_key',
                    'value' => '',
                    'value_type' => 'secret',
                    'encrypted' => 0,
                    'updated_at' => $now,
                    'updated_by' => 'system',
                ],
                [
                    'key' => 'firebase.auth_domain',
                    'value' => 'saso-willenedition.firebaseapp.com',
                    'value_type' => 'string',
                    'encrypted' => 0,
                    'updated_at' => $now,
                    'updated_by' => 'system',
                ],
                [
                    'key' => 'firebase.project_id',
                    'value' => 'saso-willenedition',
                    'value_type' => 'string',
                    'encrypted' => 0,
                    'updated_at' => $now,
                    'updated_by' => 'system',
                ],
                [
                    'key' => 'firebase.storage_bucket',
                    'value' => 'saso-willenedition.firebasestorage.app',
                    'value_type' => 'string',
                    'encrypted' => 0,
                    'updated_at' => $now,
                    'updated_by' => 'system',
                ],
                [
                    'key' => 'firebase.messaging_sender_id',
                    'value' => '278216727687',
                    'value_type' => 'string',
                    'encrypted' => 0,
                    'updated_at' => $now,
                    'updated_by' => 'system',
                ],
                [
                    'key' => 'firebase.app_id',
                    'value' => '1:278216727687:web:e180419102f4c166c32c22',
                    'value_type' => 'string',
                    'encrypted' => 0,
                    'updated_at' => $now,
                    'updated_by' => 'system',
                ],
            ])
            ->saveData();

        // Add Feature Flags
        $this->table('feature_flag')
            ->insert([
                [
                    'key_name' => 'firebase.auth',
                    'description' => 'Enable Firebase Authentication',
                    'enabled' => 1,
                    'rollout_percent' => 100,
                    'error_threshold' => 0,
                    'error_window_min' => 60,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key_name' => 'firebase.firestore',
                    'description' => 'Enable Firebase Firestore',
                    'enabled' => 0,
                    'rollout_percent' => 100,
                    'error_threshold' => 0,
                    'error_window_min' => 60,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key_name' => 'firebase.notifications',
                    'description' => 'Enable Firebase Push Notifications',
                    'enabled' => 0,
                    'rollout_percent' => 100,
                    'error_threshold' => 0,
                    'error_window_min' => 60,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key_name' => 'firebase.abtest',
                    'description' => 'Enable Firebase A/B Testing',
                    'enabled' => 0,
                    'rollout_percent' => 100,
                    'error_threshold' => 0,
                    'error_window_min' => 60,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key_name' => 'firebase.remote_config',
                    'description' => 'Enable Firebase Remote Config',
                    'enabled' => 0,
                    'rollout_percent' => 100,
                    'error_threshold' => 0,
                    'error_window_min' => 60,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key_name' => 'feature.custom_flags',
                    'description' => 'Enable generic custom feature flags',
                    'enabled' => 1,
                    'rollout_percent' => 100,
                    'error_threshold' => 0,
                    'error_window_min' => 60,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])
            ->saveData();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM system_setting WHERE `key` LIKE 'firebase.%'");
        $this->execute("DELETE FROM feature_flag WHERE key_name IN ('firebase.auth', 'firebase.firestore', 'firebase.notifications', 'firebase.abtest', 'firebase.remote_config', 'feature.custom_flags')");
    }
}
