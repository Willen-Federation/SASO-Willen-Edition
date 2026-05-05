<?php
namespace saso\barcode;

use saso\entity\Label;
use saso\framework\Setter;
use saso\framework\View;
use saso\pdf\Pdf;

final class PrintSheetView implements View
{
    use Setter;
    public array $codes  = [];
    public array $layout = ['cols' => 3, 'rows' => 8, 'w' => 70.0, 'h' => 37.0];

    public function __construct(private \Closure $inside)
    {
    }

    public function display(): void
    {
        $layout = $this->layout;
        $codes  = $this->codes;

        $label = new Label(
            '0',
            10.0,          // marginTop
            10.0,          // marginLeft
            $layout['w'],  // width
            $layout['h'],  // height
            0.0,           // intervalColomn
            0.0,           // intervalRow
        );

        $contents = (function () use ($codes) {
            foreach ($codes as $code) {
                yield $code->code->asString();
            }
        })();

        Pdf::output($label, $contents, function ($pdf, $label, $data, $x, $y) {
            $style = [
                'position'     => '',
                'align'        => 'C',
                'stretch'      => false,
                'fitwidth'     => true,
                'cellfitalign' => '',
                'border'       => false,
                'hpadding'     => 'auto',
                'vpadding'     => 'auto',
                'fgcolor'      => [0, 0, 0],
                'bgcolor'      => false,
                'text'         => true,
                'font'         => 'helvetica',
                'fontsize'     => 8,
                'stretchtext'  => 4,
            ];
            $pdf->write1DBarcode($data, 'C128', $x + 2, $y + 2, $label->width - 4, $label->height - 4, 0.4, $style, 'N');
        });
    }

    public function onRoot(): bool         { return false; }
    public function getTitle(): string     { return 'Barcode Sheet'; }
    public function getContent(): \Closure { return fn () => ''; }
}
