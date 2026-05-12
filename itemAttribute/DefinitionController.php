<?php
namespace saso\itemAttribute;

use saso\common\EmptyIO;
use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\GetterAndAnother;
use saso\util\monad\Either;

final class DefinitionController implements Controller, DTO, GettableController
{
    use DirectInput;
    use GetterAndAnother;

    private Either $defId;
    private Either $definition;
    private DTO $another;

    private static array $validTypes = [
        'string', 'int', 'float', 'bool', 'enum', 'barcode', 'multi_select', 'tags',
    ];

    public function __construct(
        private array $request,
        ?GettableController $anotherCtrl = null,
    ) {
        $this->defId = Either::fromNullable(filter_var(
            $this->request['id'] ?? '',
            \FILTER_VALIDATE_INT,
            ['options' => ['default' => false]],
        ));

        $this->definition = $this->buildDefinition();
        $this->another = $anotherCtrl ?? new EmptyIO();
    }

    private function buildDefinition(): Either
    {
        $code = preg_replace('/[^a-z0-9._]/', '', strtolower((string)($this->request['code'] ?? '')));
        if ($code === '') {
            return Either::left('code is required.');
        }

        $labelJa = trim((string)($this->request['label_ja'] ?? ''));
        if ($labelJa === '') {
            return Either::left('label_ja is required.');
        }

        $labelEn = trim((string)($this->request['label_en'] ?? ''));
        if ($labelEn === '') {
            $labelEn = $labelJa;
        }

        $valueType = (string)($this->request['value_type'] ?? 'string');
        if (!in_array($valueType, self::$validTypes, true)) {
            return Either::left('value_type is invalid.');
        }

        $unit = trim((string)($this->request['unit'] ?? '')) ?: null;

        $required = (bool)($this->request['required'] ?? false);

        $enumValues = null;
        if (in_array($valueType, ['enum', 'multi_select'], true)) {
            $raw = trim((string)($this->request['enum_values'] ?? ''));
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                $enumValues = is_array($decoded) ? json_encode($decoded) : null;
            }
        }

        $sortOrder = (int)($this->request['sort_order'] ?? 0);
        $showOnWeb = isset($this->request['show_on_web']) ? (bool)$this->request['show_on_web'] : true;
        $showOnMobile = isset($this->request['show_on_mobile']) ? (bool)$this->request['show_on_mobile'] : true;

        return Either::of([
            'code'             => $code,
            'label_ja'         => $labelJa,
            'label_en'         => $labelEn,
            'value_type'       => $valueType,
            'unit'             => $unit,
            'required'         => $required,
            'enum_values'      => $enumValues,
            'validation_regex' => null,
            'sort_order'       => $sortOrder,
            'show_on_web'      => $showOnWeb,
            'show_on_mobile'   => $showOnMobile,
        ]);
    }
}
