<?php
namespace saso\barcode;

use saso\framework\Presenter;
use saso\framework\View;
use saso\framework\Setter;
use saso\entity\Label;
use saso\pdf\Pdf;

final class PrintSheetView implements View, Presenter
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

        $label = new Label(
            '0',
            10.0,          // marginTop
            10.0,          // marginLeft
            $layout['w'],  // width
            $layout['h'],  // height
            0.0,           // intervalColomn
            0.0,           // intervalRow
        );

        $contents = (function() use ($codes) {
            foreach ($codes as $code) {
                yield $code->code->asString();
            }
        })();

        Pdf::output($label, $contents, function($pdf, $label, $data, $x, $y) {
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
            $pdf->write1DBarcode($data, 'C128', $x + 2, $y + 2, $label->width - 4, $label->height - 4, 0.4, $style, 'N');
        });
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
