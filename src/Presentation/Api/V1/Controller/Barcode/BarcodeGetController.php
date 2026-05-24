<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Barcode;

use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use saso\entity\Item;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\HttpResponse;
use Saso\Presentation\Api\V1\Response\JsonResponse;
use Saso\Presentation\Api\V1\Response\ProblemResponse;
use saso\repository\DbFinder;
use saso\repository\item\FindOneById;

final class BarcodeGetController
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
    ) {
    }

    public function handle(HttpRequest $request): HttpResponse
    {
        $codeString = strtoupper((string) ($request->pathParams['code'] ?? ''));
        try {
            $code = new BarcodeCode($codeString);
        } catch (\InvalidArgumentException) {
            return ProblemResponse::notFound('SASO-BARCODE-4001', 'Invalid barcode format.');
        }

        $row = $this->barcodes->findByCode($code);
        if ($row === null) {
            return ProblemResponse::notFound('SASO-BARCODE-4004', 'Barcode not found.');
        }

        $itemInfo = null;
        if ($row->linkedItemId !== null) {
            $finder = new DbFinder();
            // DbFinder::current() returns Either<Item> — Right(Item) on hit,
            // Left(false) on miss. The legacy monad uses isRight/getOrElse,
            // not isJust/get; calling the latter raises a fatal Error.
            $itemEntity = $finder
                ->current(new FindOneById(), ['id' => $row->linkedItemId])
                ->getOrElse(null);
            if ($itemEntity instanceof Item) {
                // saso\entity\Item exposes private fields through __get.
                $itemInfo = [
                    'id'   => $row->linkedItemId,
                    /** @phpstan-ignore-next-line property.private */
                    'name' => (string) $itemEntity->name,
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
