<?php
namespace saso\item;

use saso\util\monad;
use saso\entity;
use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;
use saso\util\Each;

final class RegisterController implements Controller
{
    use Input;
    private DTO $data;
    public function __construct(
        array $post,
        \DateTime $now,
    )
    {
        $name = entity\Item::nameConstraint($post['itemName']??'');
        $categoryId = monad\Maybe::of($post['categoryId']??'')
            ->filter(fn($v)=>!empty($v));
        $price = entity\ItemVar::priceConstraint($post['price']??'');
        $explodeByComma = fn($train)=>array_values(
            array_filter(array_map(
                fn($name)=>trim($name),
                explode(',', $train),
            ))
        );
        $colorNames = $explodeByComma($post['colorName']??'');
        $colors = monad\Either::of(array_keys($colorNames))
            ->filter(fn($v)=>!empty($v))
            ->map(fn($v)=>Each::t($v))
            ->map(Each::tf(fn($v)=>new RegisterColorInputData(
                entity\Feature::validateCode($v),
                entity\Color::nameConstraint($colorNames[$v])
            )));
        $sizeNames = $explodeByComma($post['sizeName']??'');
        $sizes = monad\Either::of(array_keys($sizeNames))
            ->filter(fn($v)=>!empty($v))
            ->map(fn($v)=>Each::t($v))
            ->map(Each::tf(fn($v)=>new RegisterSizeInputData(
                entity\Feature::validateCode($v),
                entity\Size::nameConstraint($sizeNames[$v]),
                entity\Size::orderNumberConstraint($v),
            )));
        $pla = array_key_exists('pla', $post);
        $plaNote = entity\Item::caseNoteConstraint($post['plaNote']??'')
            ->map(fn($v)=>$pla?$v:'');
        $paper = array_key_exists('paper', $post);
        $paperNote = entity\Item::caseNoteConstraint($post['paperNote']??'')
            ->map(fn($v)=>$paper?$v:'');
        $note = entity\Item::noteConstraint(trim((string)($post['note']??'')));
        $janCode = entity\Item::janCodeConstraint(trim((string)($post['janCode']??'')));
        $isbnCode = entity\Item::isbnCodeConstraint(trim((string)($post['isbnCode']??'')));
        $this->data = new RegisterInputData(
            $name,
            $categoryId,
            $price,
            $colors,
            $sizes,
            $pla,
            $plaNote,
            $paper,
            $paperNote,
            $note,
            $janCode,
            $isbnCode,
            $now,
            entity\Feature::validFeaturesAmount(count($colorNames)*count($sizeNames)),
        );
    }
}
