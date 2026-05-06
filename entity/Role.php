<?php

namespace saso\entity;

final class Role
{
    /** All available permission keys with their display labels. */
    public const PERMISSIONS = [
        'item'         => '商品管理',
        'category'     => '分類管理',
        'label'        => 'ラベル管理',
        'shelf'        => '棚番管理',
        'barcode'      => 'バーコード',
        'verify'       => 'データ照合',
        'archive'      => 'アーカイブ',
        'scanStock'    => 'スキャン在庫',
        'search'       => '検索',
        'member'       => 'メンバー管理',
        'settingAdmin' => 'システム設定',
        'featureAdmin' => 'フィーチャーフラグ',
        'authExt'      => '認証プロバイダー',
        'admin'        => 'AI設定・管理',
    ];

    /** @param list<string> $permissions */
    public function __construct(
        private string $name,
        private string $label,
        private array  $permissions = [],
    ) {}

    public function __get(string $key): mixed
    {
        return $this->$key;
    }

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->permissions, true);
    }
}
