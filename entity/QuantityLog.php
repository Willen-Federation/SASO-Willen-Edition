<?php
namespace saso\entity;

final class QuantityLog
{
    private function __construct(
        private int $fluctuation,
        private bool $isInventory,
        private \DateTime $changeAt,
    )
    {
        assert(
            $this->fluctuation !== 0 || $this->isInventory,
            'zero shipment or stock.'
        );
    }
    public static function fromFramework(
        int $fluctuationAbs,
        string $kind,
        \DateTime $changeAt
    ): self
    {
        assert($fluctuationAbs >= 0, 'abs is negative');
        return new self(
            ($kind === 'shipment'?-1:1)*$fluctuationAbs,
            $kind === 'inventory',
            $changeAt
        );
    }
    public static function fromRepository(
        int $fluctuation,
        bool $inventoryFlag,
        \DateTime $changeAt
    ): self
    {
        return new self($fluctuation, $inventoryFlag, $changeAt);
    }
    public function __get($name)
    {
        return $this->$name;
    }
}