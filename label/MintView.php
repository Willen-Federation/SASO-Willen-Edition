<?php
namespace saso\label;

use saso\entity\Label;
use saso\framework\View;
use saso\pdf\Pdf;

final class MintView implements View
{
    public array $codes  = [];
    public array $layout = ['w' => 70.0, 'h' => 37.0];

    public function display(): void
    {
        $label = new Label(
            '0',
            10.0,                  // marginTop
            10.0,                  // marginLeft
            $this->layout['w'],   // width (label_width_mm)
            $this->layout['h'],   // height (label_height_mm)
            0.0,                   // intervalColomn
            0.0,                   // intervalRow
        );

        $codes = $this->codes;
        $contents = (function () use ($codes) {
            foreach ($codes as $code) {
                yield $code->code->asString();
            }
        })();

        Pdf::output($label, $contents, function ($pdf, $label, $data, $x, $y) {
            $style = [
                'position'      => '',
                'align'         => 'C',
                'stretch'       => false,
                'fitwidth'      => true,
                'cellfitalign'  => '',
                'border'        => false,
                'hpadding'      => 'auto',
                'vpadding'      => 'auto',
                'fgcolor'       => [0, 0, 0],
                'bgcolor'       => false,
                'text'          => true,
                'font'          => 'helvetica',
                'fontsize'      => 8,
                'stretchtext'   => 4,
            ];
            $pdf->write1DBarcode($data, 'C128', $x + 2, $y + 2, $label->width - 4, $label->height - 4, 0.4, $style, 'N');
        });
    }

    public function onRoot(): bool   { return false; }
    public function getTitle(): string { return 'Barcode Labels'; }
    public function getContent(): \Closure { return fn () => ''; }
}
