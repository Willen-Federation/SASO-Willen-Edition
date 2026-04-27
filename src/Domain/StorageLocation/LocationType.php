<?php

declare(strict_types=1);

namespace Saso\Domain\StorageLocation;

/**
 * Physical classification of a storage location node.
 *
 * Hierarchy (typical, not enforced): Facility → Zone → Aisle → Rack → Shelf → Tier → Bin
 *
 *   facility  施設・建物   — Entire building or warehouse compound
 *   zone      ゾーン      — Named area inside a facility (cold storage, hazmat, etc.)
 *   aisle     通路・列    — Walking aisle / row identifier between racks
 *   rack      ラック      — A physical rack unit (contains multiple shelves)
 *   shelf     棚          — One shelf on a rack (identified by horizontal position)
 *   tier      段          — One tier / level within a shelf (vertical position)
 *   bin       棚区画      — Smallest addressable cell; default for leaf nodes
 */
enum LocationType: string
{
    case Facility = 'facility';
    case Zone     = 'zone';
    case Aisle    = 'aisle';
    case Rack     = 'rack';
    case Shelf    = 'shelf';
    case Tier     = 'tier';
    case Bin      = 'bin';

    /** Japanese label for display. */
    public function labelJa(): string
    {
        return match ($this) {
            self::Facility => '施設・建物',
            self::Zone     => 'ゾーン',
            self::Aisle    => '通路・列',
            self::Rack     => 'ラック',
            self::Shelf    => '棚',
            self::Tier     => '段',
            self::Bin      => '棚区画',
        };
    }

    /** English label for display. */
    public function labelEn(): string
    {
        return match ($this) {
            self::Facility => 'Facility',
            self::Zone     => 'Zone',
            self::Aisle    => 'Aisle',
            self::Rack     => 'Rack',
            self::Shelf    => 'Shelf',
            self::Tier     => 'Tier',
            self::Bin      => 'Bin',
        };
    }
}
