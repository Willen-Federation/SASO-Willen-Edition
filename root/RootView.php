<?php
namespace saso\root;

use saso\framework\Setter;
use saso\framework\View;

final class RootView implements View
{
    use Setter;
    private \Closure $content;
    private View $insideView;
    private string $baseUrl;
    private string $version;
    private bool $authed;
    private string $matter;
    private string $action;
    private string $currentLocale;
    /** @var list<string> */
    private array $supportedLocales;
    /** @var list<array{type:string,label:string,items:array}> */
    private array $sidebar = [];
    /** @var list<array{label:string,href?:string}> */
    private array $breadcrumb = [];

    public function __construct(
        private \Closure $inside,
    ) {
    }

    public function display(): void
    {
        $this->insideView = ($this->inside)($this->matter, $this->action);
        $this->insideView->display();

        // Compose sidebar + breadcrumb. These depend on $this->matter, the
        // current page title, and the translator — all available now.
        $this->sidebar    = $this->buildSidebar();
        $this->breadcrumb = $this->buildBreadcrumb();

        require_once 'root/template/root.php';
    }

    public function onRoot(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getContent(): \Closure
    {
        if (!$this->insideView->onRoot()) {
            return fn () => null;
        }

        return $this->content;
    }

    /**
     * @return list<array{type:string,label:string,items:array}>
     */
    private function buildSidebar(): array
    {
        $t = static fn (string $k, string $fallback): string => __($k, [], null, $fallback);
        $svg = static function (string $name): string {
            ob_start();
            ui('iconHeroicon', ['name' => $name, 'class' => 'menu-item-icon h-5 w-5']);
            return (string) ob_get_clean();
        };

        return [
            [
                'type'  => 'group',
                'label' => $t('ui.sidebar.group.main', 'Main'),
                'items' => [
                    ['key' => 'home',         'label' => $t('ui.sidebar.home',         'Home'),                    'href' => '/',               'icon' => $svg('home')],
                    ['key' => 'search',       'label' => $t('ui.sidebar.search',       'Search'),                  'href' => '/search/start/',  'icon' => $svg('list')],
                    ['key' => 'label_first',  'label' => $t('ui.sidebar.label_first',  'Print → register'),        'href' => '/label/wizard/',  'icon' => $svg('sparkles'), 'new' => true],
                ],
            ],
            [
                'type'  => 'group',
                'label' => $t('ui.sidebar.group.inventory', 'Inventory'),
                'items' => [
                    [
                        'key'      => 'item',
                        'label'    => $t('ui.sidebar.item', 'Items'),
                        'icon'     => $svg('box'),
                        'children' => [
                            ['label' => $t('ui.sidebar.item_register',   'Register'),     'href' => '/item/add/'],
                            ['label' => $t('ui.sidebar.item_archive',    'Archive list'), 'href' => '/archive/list/'],
                            ['label' => $t('ui.sidebar.item_archiveAll', 'Archive all'),  'href' => '/item/archivingAll/'],
                        ],
                    ],
                    [
                        'key'      => 'shelf',
                        'label'    => $t('ui.sidebar.shelf', 'Shelves'),
                        'icon'     => $svg('grid'),
                        'children' => [
                            ['label' => $t('ui.sidebar.shelf_create', 'Create'), 'href' => '/shelf/start/'],
                            ['label' => $t('ui.sidebar.shelf_map',    'Map'),    'href' => '/shelf/map/', 'new' => true],
                        ],
                    ],
                    ['key' => 'verify',     'label' => $t('ui.sidebar.verify',     'Verification'), 'href' => '/verify/start/', 'icon' => $svg('check-square'), 'new' => true],
                    ['key' => 'drafts',     'label' => $t('ui.sidebar.drafts',     'Draft Items'),  'href' => '/item/drafts/', 'icon' => $svg('clock'), 'new' => true],
                    ['key' => 'scan_stock', 'label' => $t('ui.sidebar.scan_stock', 'Scan & Stock'), 'href' => '/scan/stock/', 'icon' => $svg('qr-code'), 'new' => true],
                ],
            ],
            [
                'type'  => 'group',
                'label' => $t('ui.sidebar.group.label', 'Labels'),
                'items' => [
                    [
                        'key'      => 'label',
                        'label'    => $t('ui.sidebar.label', 'Label'),
                        'icon'     => $svg('printer'),
                        'children' => [
                            ['label' => $t('ui.sidebar.label_print', 'Print'), 'href' => '/label/features/'],
                            ['label' => $t('ui.sidebar.label_size',  'Sizes'), 'href' => '/label/start/'],
                        ],
                    ],
                ],
            ],
            [
                'type'  => 'group',
                'label' => $t('ui.sidebar.group.admin', 'Administration'),
                'items' => [
                    ['key' => 'category', 'label' => $t('ui.sidebar.category',        'Categories'),    'href' => '/category/start/',      'icon' => $svg('tag')],
                    ['key' => 'users',    'label' => $t('ui.sidebar.users',           'Users'),         'href' => '/member/start/',        'icon' => $svg('users')],
                    ['key' => 'flags',    'label' => $t('ui.sidebar.flags',           'Feature flags'), 'href' => '/admin/feature-flags/', 'icon' => $svg('toggle'),  'new' => true],
                    ['key' => 'auth',     'label' => $t('ui.sidebar.auth_providers', 'Auth providers'),'href' => '/auth/providers/',     'icon' => $svg('shield'),  'new' => true],
                    ['key' => 'password',    'label' => $t('ui.sidebar.password',     'Password'),     'href' => '/start/password/',      'icon' => $svg('key')],
                    ['key' => 'ai_settings', 'label' => $t('ui.sidebar.ai_settings', 'AI Settings'),  'href' => '/admin/ai-settings/',   'icon' => $svg('sparkles')],
                ],
            ],
        ];
    }

    /**
     * @return list<array{label:string,href?:string}>
     */
    private function buildBreadcrumb(): array
    {
        $title = $this->insideView->getTitle();
        if ($title === '') {
            return [];
        }
        $crumbs = [
            ['label' => __('ui.nav.home', [], null, 'Home'), 'href' => '/'],
        ];
        if ($this->matter !== '' && $this->matter !== 'start') {
            $crumbs[] = ['label' => $title];
        }
        return $crumbs;
    }
}
