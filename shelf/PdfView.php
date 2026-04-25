<?php
namespace saso\shelf;

use saso\entity;
use saso\framework\Setter;
use saso\framework\View;
use saso\pdf\Pdf;

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
            ($this->inside)('shelf', 'shelves')->getContent()(),
            function(\TCPDF $pdf, $label, $data, $x, $y) {
                $pdf->MultiCell($label->width, $label->height/12, '', 0, 'C', 0, 1, $x, $y);
                $pdf->MultiCell($label->width, 22, 
                    $data,
                0, 'L', 0, 1, $x+5);
                $pdf->write1DBarcode($data, 'C128A',$x,$label->height/12+12+$y,'',$label->height*5/12,0.3,['padding'=>0],'N');
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
