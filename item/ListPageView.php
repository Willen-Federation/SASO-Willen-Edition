<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

/**
 * Top-level page wrapper around ListFrameView.
 *
 * /item/listFrame/ exists primarily as an onRoot=false fragment that the
 * home dashboard, archive page, and search page embed via
 * ($v->inside)('item','listFrame'). Navigating to it directly would echo
 * the fragment before <!DOCTYPE> and produce a whiteout.
 *
 * This wrapper mirrors ArchiveListView: onRoot=true so RootView wraps it
 * with Tabler chrome, then the template embeds listFrame as an inside
 * fragment in the proper place inside the body.
 */
final class ListPageView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private bool $isArchive = false;
    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        require_once 'item/template/listPage.php';
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
