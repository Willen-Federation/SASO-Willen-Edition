<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Barcode;

use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\HttpResponse;
use Saso\Presentation\Api\V1\Response\JsonResponse;
use Saso\Presentation\Api\V1\Response\ProblemResponse;
use saso\repository\color\FindByItem as FindColorsByItem;
use saso\repository\DbFinder;
use saso\repository\item\FindOneById;
use saso\repository\size\FindByItem as FindSizesByItem;

final class BarcodeGetController
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
    ) {
    }

    public function handle(HttpRequest $request): HttpResponse
    {
        $codeString = (string) ($request->pathParams['code'] ?? '');
        try {
            $code = new BarcodeCode($codeString);
        } catch (\InvalidArgumentException) {
            return ProblemResponse::notFound('SASO-BARCODE-4001', "Invalid barcode format: $codeString");
        }

        $row = $this->barcodes->findByCode($code);
        if ($row === null) {
            return ProblemResponse::notFound('SASO-BARCODE-4004', "Barcode not found: $codeString");
        }

        $itemInfo = null;
        if ($row->linkedItemId !== null) {
            $finder = new DbFinder();
            $itemEntity = $finder->current(new FindOneById(), ['id' => $row->linkedItemId])->getOrElse(null);
            if ($itemEntity !== null) {
                $firstColor = $finder->current(new FindColorsByItem($itemEntity))->getOrElse(null);
                $firstSize  = $finder->current(new FindSizesByItem($itemEntity))->getOrElse(null);
                $itemInfo = [
                    'id'        => $row->linkedItemId,
                    'name'      => $itemEntity->name,
                    'colorCode' => $firstColor?->code,
                    'sizeCode'  => $firstSize?->code,
                ];
            }
        }

        return new JsonResponse(
            status: 200,
            body: [
                'code'   => $row->code->asString(),
                'status' => $row->status->value,
                'item'   => $itemInfo,
            ],
        );
    }
}
