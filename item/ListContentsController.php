<?php
namespace saso\item;

use saso\entity\Config;
use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;

final class ListContentsController implements Controller
{
    use Input;
    private DTO $data;
    public function __construct(
        array $query,
        array $config,
        bool $isArchive,
    )
    {
        $containedOrNull = fn($list)=>fn($input)=>in_array($input, $list)?$input:null;
        $sortbyList = [
            'concatId',
            'categoryId',
            'price',
            'createAt',
            'updateAt',
        ];
        $directionList = [
            'desc',
            'asc',
        ];
        $args = [
            'page'=>[
                'filter'=>\FILTER_VALIDATE_INT,
                'options'=>['default'=>1],
            ],
            'sortby'=>[
                'filter'=>\FILTER_CALLBACK,
                'options'=>$containedOrNull($sortbyList),
            ],
            'direction'=>[
                'filter'=>\FILTER_CALLBACK,
                'options'=>$containedOrNull($directionList),
            ],
            'search'=>[
                'filter'=>\FILTER_CALLBACK,
                'options'=>fn($v)=>urldecode(preg_replace('/\//', '', $v)),
            ],
        ];
        $nullableData = filter_var_array($query, $args);
        $default = [
            'page'=>'1',
            'sortby'=>'createAt',
            'direction'=>'desc',
            'search'=>'',
        ];
        $outputRow = Config::outputRowConstraint($config);
        $this->data = new ListContentsInputData(
            $outputRow,
            $isArchive,
            ...array_map(
                fn($key)=>$nullableData[$key]??$default[$key],
                array_keys($nullableData),
            )
        );
    }
}
