<?php
namespace saso\barcode;

use saso\framework\View;
use saso\framework\Setter;
use saso\entity\Label;
use saso\pdf\Pdf;

final class PrintSheetView implements View
{
    use Setter;
    private array $data;

    public function __construct(private \Closure $inside)
    {
    }

    public function present(mixed $data): void
    {
        $this->data = $data;
    }

    public function display(): void
    {
        $layout = $this->data['layout'];
        $codes = $this->data['codes'];
        $columns = max(1, (int) ($layout['cols'] ?? 1));
        $rows = max(1, (int) ($layout['rows'] ?? 1));
        $width = (float) $layout['w'];
        $height = (float) $layout['h'];
        $marginLeft = max(0.0, (210.0 - ($columns * $width)) / 2);
        $marginTop = max(0.0, (297.0 - ($rows * $height)) / 2);
        $codeType = (string) ($layout['codeType'] ?? 'C128');

        // Mock a Label entity for the legacy Pdf class
        $label = new Label(
            '0',
            $marginTop,
            $marginLeft,
            $width,
            $height,
            0.0,  // intervalColomn
            0.0,  // intervalRow
        );

        $contents = (function() use ($codes) {
            foreach ($codes as $code) {
                yield $code->code->asString();
            }
        })();

        Pdf::output($label, $contents, function($pdf, $label, $data, $x, $y) use ($codeType) {
            // Draw barcode
            $style = [
                'position' => '',
                'align' => 'C',
                'stretch' => false,
                'fitwidth' => true,
                'cellfitalign' => '',
                'border' => false,
                'hpadding' => 'auto',
                'vpadding' => 'auto',
                'fgcolor' => [0,0,0],
                'bgcolor' => false,
                'text' => true,
                'font' => 'helvetica',
                'fontsize' => 8,
                'stretchtext' => 4
            ];
            if ($codeType === 'C128' || $codeType === 'EAN13') {
                $pdf->write1DBarcode($data, $codeType, $x + 2, $y + 2, $label->width - 4, $label->height - 4, 0.4, $style, 'N');
                return;
            }

            $square = max(8.0, min($label->width, $label->height) - 6);
            $qrX = $x + max(2.0, ($label->width - $square) / 2);
            $qrY = $y + max(2.0, ($label->height - $square) / 2);
            $pdf->write2DBarcode($data, $codeType, $qrX, $qrY, $square, $square, [
                'border' => false,
                'padding' => 0,
                'fgcolor' => [0, 0, 0],
                'bgcolor' => false,
            ], 'N');
        }, $columns, $rows);
    }

    public function onRoot(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return 'Barcode Sheet';
    }

    public function getContent(): \Closure
    {
        return fn() => '';
    }
}
