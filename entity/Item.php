<?php

namespace saso\entity;

use saso\util\monad\Either;

final class Item
{
    private string $dateCode;
    private string $id;
    public function __construct(
        private string $serial,
        private string $name,
        private bool $pla,
        private ?string $plaNote,
        private bool $paper,
        private ?string $paperNote,
        private \DateTime $createAt,
        private ?string $status = null,
    )
    {
        $this->dateCode = self::makeDateCode($createAt);
        $this->id = $this->dateCode.sprintf('%04d', $serial);
    }
    public function __get($name)
    {
        return $this->$name;
    }
    public static function nameConstraint(string $name): Either
    {
        return EntityConstraint::requiredStringConstraint($name, 50);
    }
    public static function caseNoteConstraint(string $note): Either
    {
        return EntityConstraint::optionalNoteConstraint($note, 50);
    }
    public static function makeDateCode(\DateTime $dt): string
    {
        return $dt->format('ym');
    }
    public static function idConstraint(string $id): Either
    {
        return Either::fromNullable(filter_var(
            $id,
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^\d{8}$/'
                ]
            ]
        ));
    }
}
