<?php
namespace saso\entity;

use saso\util\Each;
use saso\util\monad\Either;

final class QuantityLogs implements \IteratorAggregate
{
    private int $sum = 0;
    private bool $empty = true;
    /** @property Either<Each<QuantityLog>> $history */
    private Either $history;
    public function __construct(
        private Feature $feature,
        Either $history,
    )
    {
        $this->history = $history->flatMap(
            Each::tf(function($v) {
                $this->sum += $v->fluctuation;
                $this->empty = false;
                return $v;
            })
        );
    }
    public function __get($name)
    {
        if($name === 'feature'){
            return $this->feature;
        }
    }
    public function addable(QuantityLog $adding): bool
    {
        return ($adding->isInventory && $adding->fluctuation >= 0)
        || ($this->isInventoried() && !$adding->isInventory && $this->sum() + $adding->fluctuation >= 0);
    }
    public function getIterator(): \Generator
    {
        return $this->history->getOrElse(Each::t([]));
    }
    private function generate(): void
    {
        $this->history->filter(
            fn($v)=>$v->valid()
        )->flatMap(Each::exec(fn($v)=>$v))->getOrElse(null);
    }
    public function sum(): int
    {
        $this->generate();
        return $this->sum;
    }
    public function isInventoried(): bool
    {
        $this->generate();
        return !$this->empty;
    }
}
