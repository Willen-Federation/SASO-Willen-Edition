<?php

declare(strict_types=1);

namespace Saso\Domain\ItemDraft;

enum ItemDraftStatus: string
{
    case Queued     = 'queued';
    case Processing = 'processing';
    case Ready      = 'ready';
    case Failed     = 'failed';
    case Confirmed  = 'confirmed';
    case Discarded  = 'discarded';
}
