<?php
namespace saso\item;

use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;
use saso\framework\Getter;
use saso\util\monad;
use saso\entity;
use saso\util\Each;

final class RegisterFromBarcodeController implements Controller, DTO
{
    use Input;
    public function __get($prop) { return $this->$prop; }
    public readonly string $barcodeId;
    public readonly RegisterInputData $registerData;

    public function __construct(array $post, \DateTime $now)
    {
        $this->barcodeId = (string) ($post['barcodeId'] ?? '');

        $name = entity\Item::nameConstraint($post['itemName'] ?? '');
        $categoryId = monad\Maybe::of($post['categoryId'] ?? '')->filter(fn($v) => !empty($v));
        $price = entity\ItemVar::priceConstraint($post['price'] ?? '');

        $explodeByComma = fn($train) => array_values(
            array_filter(array_map(fn($v) => trim($v), explode(',', $train)))
        );
        $colorNames = $explodeByComma($post['colorName'] ?? '');
        $colors = monad\Either::of(array_keys($colorNames))
            ->filter(fn($v) => !empty($v))
            ->map(fn($v) => Each::t($v))
            ->map(Each::tf(fn($v) => new RegisterColorInputData(
                entity\Feature::validateCode($v),
                entity\Color::nameConstraint($colorNames[$v])
            )));

        $sizeNames = $explodeByComma($post['sizeName'] ?? '');
        $sizes = monad\Either::of(array_keys($sizeNames))
            ->filter(fn($v) => !empty($v))
            ->map(fn($v) => Each::t($v))
            ->map(Each::tf(fn($v) => new RegisterSizeInputData(
                entity\Feature::validateCode($v),
                entity\Size::nameConstraint($sizeNames[$v]),
                entity\Size::orderNumberConstraint($v),
            )));

        $this->registerData = new RegisterInputData(
            $name,
            $categoryId,
            $price,
            $colors,
            $sizes,
            false, // pla
            entity\Item::caseNoteConstraint(''), // plaNote
            false, // paper
            entity\Item::caseNoteConstraint(''), // paperNote
            $now,
            entity\Feature::validFeaturesAmount(count($colorNames) * count($sizeNames)),
        );
    }
}
