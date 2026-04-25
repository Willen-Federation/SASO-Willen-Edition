<?php
namespace saso\label;

use saso\entity;
use saso\framework\Setter;
use saso\framework\View;
use saso\pdf\Pdf;
use saso\util\monad\Either;

/**
 * @property \Generator<entity\Feature> $features
 */
final class PdfView implements View
{
    use Setter;
    private entity\Label $label;
    public function __construct(
        private \Closure $inside
    )
    {
    }
    public function display(): void
    {
        Pdf::output(
            $this->label,
            ($this->inside)('feature', 'labelAmountFeatures')->getContent()(),
            function(\TCPDF $pdf, $label, $data, $x, $y) {
                $pdf->MultiCell($label->width, $label->height/12, '', 0, 'C', 0, 1, $x, $y);
                $pdf->MultiCell(
                    $label->width, $label->height/6,
                    entity\Pdf::shortenName(Either::of($label), Either::of($data))->getOrElse(''),
                    0, 'L', 0, 1, $x
                );
                $pdf->write1DBarcode($data->getFullCode(), 'C128A',$x,'','',$label->height/2, 0.3, ['padding'=>0], 'N');
                $pdf->MultiCell($label->width, $label->height/12, $data->getFullCode(), 0, 'L', 0, 1, $x);
                $pdf->MultiCell(
                    $label->width, $label->height/12,
                    ($data->item->paper?('<img src="./img/kami.png" width="12">' . $data->item->paperNote):'')
                    .($data->item->pla?('<img src="./img/pla.png" width="12">' . $data->item->plaNote):''),
                    0, 'L', 0, 1, $x, '', true, 0, true
                );
            }
        );
    }
    public function onRoot(): bool
    {
        return false;
    }
    public function getTitle(): string
    {
        return '';
    }
    public function getContent(): \Closure
    {
        return fn()=>null;
    }
}
