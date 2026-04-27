<?php

declare(strict_types=1);

namespace Saso\Domain\StorageLocation;

enum StorageOperationalStatus: string
{
    case Available   = 'available';
    case Receiving   = 'receiving';
    case Shipping    = 'shipping';
    case Reserved    = 'reserved';
    case NoOutbound  = 'no_outbound';
    case Maintenance = 'maintenance';
    case Full        = 'full';
    case Closed      = 'closed';

    public function labelEn(): string
    {
        return match ($this) {
            self::Available   => 'Available',
            self::Receiving   => 'Receiving',
            self::Shipping    => 'Shipping',
            self::Reserved    => 'Reserved',
            self::NoOutbound  => 'No Outbound',
            self::Maintenance => 'Maintenance',
            self::Full        => 'Full',
            self::Closed      => 'Closed',
        };
    }

    public function labelJa(): string
    {
        return match ($this) {
            self::Available   => '利用可能',
            self::Receiving   => '入庫中',
            self::Shipping    => '出庫中',
            self::Reserved    => 'キープ',
            self::NoOutbound  => '出庫禁止',
            self::Maintenance => 'メンテナンス中',
            self::Full        => '満杯',
            self::Closed      => '閉鎖',
        };
    }

    public function canReceive(): bool
    {
        return match ($this) {
            self::Available, self::Receiving => true,
            default                          => false,
        };
    }

    public function canShip(): bool
    {
        return match ($this) {
            self::Available, self::Shipping, self::Reserved => true,
            default                                         => false,
        };
    }
}
