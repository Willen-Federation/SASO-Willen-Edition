<?php
namespace saso\barcode;

use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use Saso\Domain\Barcode\BarcodeCode;

final class PrintSheetController implements Controller, DTO
{
    use DirectInput;

    public function __get($prop)
    {
        return $this->$prop;
    }

    public readonly string $layoutId;
    public readonly int $cols;
    public readonly int $rows;
    public readonly float $wMm;
    public readonly float $hMm;
    public readonly string $prefix;
    public readonly int $startNo;
    public readonly int $count;
    public readonly string $codeType;

    public function __construct(array $post)
    {
        $this->layoutId = (string) ($post['layoutId'] ?? 'custom');
        $this->cols = max(1, min(10, (int) ($post['cols'] ?? 3)));
        $this->rows = max(1, min(20, (int) ($post['rows'] ?? 8)));
        $this->wMm = max(10.0, min(200.0, (float) ($post['wMm'] ?? 70.0)));
        $this->hMm = max(10.0, min(200.0, (float) ($post['hMm'] ?? 37.0)));
        $this->startNo = max(1, min(999_999_999, (int) ($post['startNo'] ?? 1)));
        $this->count = max(1, min(5_000, (int) ($post['count'] ?? 24)));
        $this->codeType = self::normalizeCodeType((string) ($post['codeType'] ?? 'C128'));
        $this->prefix = $this->codeType === 'EAN13'
            ? BarcodeCode::normalizeJanPrefix((string) ($post['prefix'] ?? BarcodeCode::JAN_PREFIX))
            : BarcodeCode::normalizePrefix((string) ($post['prefix'] ?? 'BC'));
    }

    private static function normalizeCodeType(string $codeType): string
    {
        $codeType = strtoupper(trim($codeType));
        return in_array($codeType, ['C128', 'EAN13', 'QRCODE,H', 'DATAMATRIX', 'PDF417'], true)
            ? $codeType
            : 'C128';
    }
}
