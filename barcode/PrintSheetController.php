<?php
namespace saso\barcode;

use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;
use saso\framework\Getter;

final class PrintSheetController implements Controller, DTO
{
    use Input;
    public function __get($prop) { return $this->$prop; }
    public readonly string $layoutId;
    public readonly int $cols;
    public readonly int $rows;
    public readonly float $wMm;
    public readonly float $hMm;
    public readonly string $prefix;
    public readonly int $startNo;
    public readonly int $count;

    public function __construct(array $post)
    {
        $this->layoutId = (string) ($post['layoutId'] ?? 'custom');
        $this->cols = (int) ($post['cols'] ?? 3);
        $this->rows = (int) ($post['rows'] ?? 8);
        $this->wMm = (float) ($post['wMm'] ?? 70.0);
        $this->hMm = (float) ($post['hMm'] ?? 37.0);
        $this->prefix = (string) ($post['prefix'] ?? 'BC');
        $this->startNo = (int) ($post['startNo'] ?? 1);
        $this->count = (int) ($post['count'] ?? 24);
    }
}
