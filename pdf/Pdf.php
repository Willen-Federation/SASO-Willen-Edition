<?php
namespace saso\pdf;

use saso\entity\Label;

final class Pdf
{
    public static function output(Label $label, \Generator $contents, \Closure $fn, ?int $columns = null, ?int $rows = null): void
    {
        require_once 'extention/TCPDF/tcpdf.php';
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('cid0jp', '', 10);
        $pdf->SetMargins($label->marginLeft,$label->marginTop);
        $pdf->SetAutoPageBreak(false);

        $pdf->AddPage('P','A4');

        $colomnlimit = $columns !== null
            ? max(1, $columns)
            : floor(($pdf->getPageWidth() - $label->marginLeft) / ($label->width + $label->intervalColomn));
        $rowlimit = $rows !== null
            ? max(1, $rows)
            : floor(($pdf->getPageHeight() - $label->marginTop) / ($label->height + $label->intervalRow));
        $perPage = $colomnlimit*$rowlimit;

        $key = 0;
        foreach($contents as $data){
            if($key > 0 && $key%$perPage == 0) {
                $pdf->AddPage('P','A4');
            }

            $x = $label->marginLeft + $label->width*($key%$perPage%$colomnlimit) + $label->intervalColomn*($key%$perPage%$colomnlimit);
            $y = $label->marginTop + $label->height*(floor(($key%$perPage)/$colomnlimit)) + $label->intervalRow*(floor(($key%$perPage)/$colomnlimit));
            
            $fn($pdf, $label, $data, $x, $y);
            $key++;
        }

        $pdf->Output("items.pdf", "I");
    }
}
