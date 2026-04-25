<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;
use saso\util\monad\Maybe;

/**
 * @property Maybe<Either<array<string>>> $colors
 * @property Maybe<Either<array<string>>> $sizes
 */
final class AddFeatureController implements GettableController, DTO
{
    use Getter;
    private Maybe $colors;
    private Maybe $sizes;
    public function __construct(
        array $post,
        private \DateTime $now,
    )
    {
        $explodeByComma = fn($train)=>Maybe::of(array_values(
            array_filter(array_map(
                fn($name)=>trim($name),
                explode(',', $train),
            ))
        ))->filter(
            fn($v)=>!empty($v)
        );
        try{
            $this->colors = $explodeByComma($post['colorName']??'')->flatMap(
                fn($v)=>array_map(
                    fn($c)=>entity\Color::nameConstraint($c)->getOrElseThrow('color name is too long.'),
                    $v
                )
            )->map(fn($v)=>Either::of($v));
        } catch (\Exception $e) {
            $this->colors = Maybe::of(Either::left($e->getMessage()));
        }
        try{
            $this->sizes = $explodeByComma($post['sizeName']??'')->flatMap(
                fn($v)=>array_map(
                    fn($s)=>entity\Size::nameConstraint($s)->getOrElseThrow('size name is too long.'),
                    $v
                )
            )->map(fn($v)=>Either::of($v));
        } catch (\Exception $e) {
            $this->sizes = Maybe::of(Either::left($e->getMessage()));
        }
    }
}