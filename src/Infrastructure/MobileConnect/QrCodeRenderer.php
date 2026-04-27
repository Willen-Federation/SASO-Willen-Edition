<?php

declare(strict_types=1);

namespace Saso\Infrastructure\MobileConnect;

/**
 * Renders a text string as a QR code PNG using the bundled TCPDF library.
 *
 * The QR data is a deep-link URI:
 *   saso://connect?token=<TOKEN>&url=<SERVER_URL>
 *
 * The PNG is returned as raw bytes so the controller can choose to
 * embed it as base64 in a JSON response or serve it directly as
 * image/png. We produce an image at "H" (high, ~30% error correction)
 * so that the image remains scannable even if partially obscured.
 */
final class QrCodeRenderer
{
    private const MODULE_SIZE = 6;
    private const MARGIN      = 2;
    private const ECC         = 'H';

    public function renderPng(string $data): string
    {
        require_once dirname(__DIR__, 3).'/extention/TCPDF/tcpdf_barcodes_2d.php';

        /** @phpstan-ignore-next-line */
        $barcode = new \TCPDF2DBarcode($data, 'QRCODE,'.self::ECC);
        /** @phpstan-ignore-next-line */
        $array = $barcode->getBarcodeArray();

        if (empty($array) || empty($array['bcode'])) {
            throw new \RuntimeException('QrCodeRenderer: TCPDF returned an empty barcode array.');
        }

        $rows = $array['num_rows'];
        $cols = $array['num_cols'];
        $size = self::MODULE_SIZE;
        $mar  = self::MARGIN * $size;
        $w    = $cols * $size + $mar * 2;
        $h    = $rows * $size + $mar * 2;

        $img = imagecreate($w, $h);
        if ($img === false) {
            throw new \RuntimeException('QrCodeRenderer: imagecreate() failed.');
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        foreach ($array['bcode'] as $r => $row) {
            foreach ($row as $c => $cell) {
                if ($cell !== 0) {
                    imagefilledrectangle(
                        $img,
                        $mar + $c * $size,
                        $mar + $r * $size,
                        $mar + $c * $size + $size - 1,
                        $mar + $r * $size + $size - 1,
                        $black,
                    );
                }
            }
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        if ($png === false || $png === '') {
            throw new \RuntimeException('QrCodeRenderer: imagepng() produced no output.');
        }

        return $png;
    }

    public function renderBase64(string $data): string
    {
        return base64_encode($this->renderPng($data));
    }
}
