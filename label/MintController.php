<?php
namespace saso\label;

use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;

final class MintController implements Controller, DTO
{
    use DirectInput;

    public readonly int $sheetLayoutId;
    public readonly int $count;

    public function __construct(array $post)
    {
        $this->sheetLayoutId = max(1, (int) ($post['sheet_layout_id'] ?? 0));
        $this->count = min(500, max(1, (int) ($post['count'] ?? 12)));
    }
}
