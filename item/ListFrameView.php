<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class ListFrameView implements View
{
    use Setter;
    private \Closure $content;
    private string $request;
    private string $searchUrl;
    public function __construct(
        private \Closure $inside,
        private bool $isArchive,
        private string $search
    )
    {
        $this->request = $this->isArchive?'archive/list':'start/start';
        $this->searchUrl = $search === ''?'':'search/'.$this->search;
    }
    public function display(): void
    {
        require_once 'item/template/listFrame.php';
    }
    public function onRoot(): bool
    {
        // ListFrameView is a fragment, not a stand-alone page. It is embedded
        // by start/template/start.php (home), search/template/start.php and
        // item/template/archiveList.php via ($v->inside)('item','listFrame').
        // That fragment use relies on Loader::insideFlow's auto-echo, which
        // only fires when onRoot=false — so this MUST stay false.
        //
        // Direct navigation to /item/listFrame/ would echo the fragment
        // before <!DOCTYPE> (whiteout). The home menu therefore points at
        // /item/list/ instead, which is wrapped by ListPageView (onRoot=true)
        // and embeds this fragment in the proper slot inside chrome.
        return false;
    }
    public function getTitle(): string
    {
        return '';
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
