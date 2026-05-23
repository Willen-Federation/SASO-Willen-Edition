<?php
namespace saso\root;

use saso\framework\Setter;
use saso\framework\View;

final class RootView implements View
{
    use Setter;
    public \Closure $content;
    public View $insideView;
    public string $baseUrl;
    public string $version;
    public bool $authed;
    public string $matter;
    public string $action;
    public string $currentLocale;
    /** @var list<string> */
    public array $supportedLocales;
    /** @var list<string> */
    public array $permissions = [];
    /** @var list<array{type:string,label:string,items:array}> */
    public array $sidebar = [];
    /** @var list<array{label:string,href?:string}> */
    public array $breadcrumb = [];

    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        $this->insideView = ($this->inside)($this->matter, $this->action);
        $this->insideView->display();
        $this->sidebar = $this->buildSidebar();
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
        return $this->content;
    }

    private function basePath(): string
    {
        return parse_url($this->baseUrl, PHP_URL_PATH) ?? '/';
    }

    /**
     * @return list<array{type:string,label:string,items:array}>
     */
    private function buildSidebar(): array
    {
        $base = $this->basePath();
        $perms = $this->permissions;
        $t = static fn (string $k, string $fallback): string => __($k, [], null, $fallback);
        $d = static fn (string $k, string $fallback): string => __($k, [], null, $fallback);
        $svg = static function (string $name): string {
            ob_start();
            ui('iconHeroicon', ['name' => $name, 'class' => 'h-5 w-5 shrink-0']);
            return (string) ob_get_clean();
        };
        $can = static fn (?string $p): bool => $p === null || in_array($p, $perms, true);
        $filterItems = static function (array $items) use ($can): array {
            return array_values(array_filter($items, fn($item) => $item !== null && $can($item['permission'] ?? null)));
        };

        $groups = [
            [
                'type'  => 'group',
                'label' => $t('ui.sidebar.group.inventory', 'Inventory'),
                'items' => $filterItems([
                    ['key' => 'home',         'label' => $t('ui.sidebar.home',          'Home'),            'description' => $d('ui.dashboard.quick_links_help', 'Open the main operations dashboard.'),        'href' => $base,                     'icon' => $svg('home'),         'permission' => null],
                    ['key' => 'item_add',     'label' => $t('ui.sidebar.item_register', 'Register'),        'description' => $d('ui.dashboard.item_register_help', 'Add a new SKU to the catalogue.'),           'href' => $base.'item/add/',          'icon' => $svg('plus-circle'), 'permission' => 'item'],
                    ['key' => 'bulk',         'label' => $t('ui.sidebar.bulk',          'Bulk operations'), 'description' => $d('ui.dashboard.bulk_help', 'Import, export, or update many items at once.'), 'href' => $base.'item/bulkImport/',   'icon' => $svg('box'),          'permission' => 'item'],
                    ['key' => 'verify',       'label' => $t('ui.sidebar.verify',        'Verification'),    'description' => $d('ui.dashboard.verify_help', 'Run stocktakes or spot-checks.'),               'href' => $base.'verify/start/',      'icon' => $svg('check-circle'),'permission' => 'verify'],
                    ['key' => 'item_archive', 'label' => $t('ui.sidebar.item_archive',  'Archive list'),    'description' => $d('ui.dashboard.archive_help', 'Browse archived items.'),                       'href' => $base.'archive/list/',      'icon' => $svg('archive'),     'permission' => 'archive'],
                ]),
            ],
            [
                'type'  => 'group',
                'label' => $t('ui.sidebar.group.label', 'Labels'),
                'items' => $filterItems([
                    ['key' => 'label_print',  'label' => $t('ui.sidebar.label_print',   'Print item labels'), 'description' => $d('ui.dashboard.label_help', 'Generate barcode labels for registered items.'), 'href' => $base.'label/features/',   'icon' => $svg('printer'),  'permission' => 'label'],
                    ['key' => 'barcode_sheet','label' => $t('ui.sidebar.barcode_sheet', 'Create barcodes'),   'description' => $d('ui.dashboard.barcode_sheet_help', 'Create, print, then attach barcode labels to items.'), 'href' => $base.'barcode/sheet/',    'icon' => $svg('qr'),       'permission' => 'barcode'],
                ]),
            ],
            [
                'type'  => 'group',
                'label' => $t('ui.sidebar.group.master', 'Master data'),
                'items' => $filterItems([
                    [
                        'key'        => 'shelf',
                        'label'      => $t('ui.sidebar.shelf', 'Shelves'),
                        'description'=> $d('ui.dashboard.shelf_help', 'Detailed shelf management and labels.'),
                        'icon'       => $svg('grid'),
                        'permission' => 'shelf',
                        'children'   => [
                            ['label' => $t('ui.sidebar.shelf_create', 'Create'),      'description' => $d('ui.dashboard.shelf_help', 'Detailed shelf management and labels.'), 'href' => $base.'shelf/start/'],
                            ['label' => $t('ui.sidebar.shelf_map',    'Map'),         'description' => $d('ui.dashboard.shelf_map_help', 'View item locations by shelf.'),       'href' => $base.'shelf/map/'],
                            ['label' => $t('ui.sidebar.shelf_simple', 'Simple setup'), 'description' => $d('ui.dashboard.shelf_simple_help', 'Quickly configure shelf ranges.'),    'href' => $base.'shelf/simple/'],
                        ],
                    ],
                    ['key' => 'category',       'label' => $t('ui.sidebar.category',       'Categories'),     'description' => $d('ui.dashboard.category_help', 'Manage the category tree.'),                    'href' => $base.'category/start/',      'icon' => $svg('tag'),         'permission' => 'category'],
                    ['key' => 'label_size',     'label' => $t('ui.sidebar.label_size',     'Label sizes'),    'description' => $d('ui.dashboard.label_size_help', 'Configure A4 sheet layouts.'),                  'href' => $base.'label/start/',         'icon' => $svg('list'),        'permission' => 'label'],
                    ['key' => 'item_attribute', 'label' => $t('ui.sidebar.item_attribute', 'Status columns'), 'description' => $d('ui.dashboard.item_attribute_help', 'Add custom status columns to items.'),       'href' => $base.'itemAttribute/start/', 'icon' => $svg('adjustments'), 'permission' => 'item'],
                ]),
            ],
            [
                'type'  => 'group',
                'label' => $t('ui.sidebar.group.admin', 'Administration'),
                'items' => $filterItems([
                    ['key' => 'member',   'label' => $t('ui.sidebar.member',            'Members'),        'description' => $d('ui.dashboard.member_help', 'Manage user accounts.'),                          'href' => $base.'member/start/',           'icon' => $svg('users'),    'permission' => 'member'],
                    ['key' => 'role',     'label' => $t('ui.sidebar.role',              'Roles'),          'description' => $d('ui.dashboard.role_help', 'Control role permissions.'),                       'href' => $base.'role/start/',             'icon' => $svg('shield'),   'permission' => 'member'],
                    ['key' => 'flags',    'label' => $t('ui.sidebar.flags',             'Feature flags'),  'description' => $d('ui.dashboard.flags_help', 'Turn optional features on or off.'),               'href' => $base.'admin/feature-flags/',    'icon' => $svg('toggle'),   'permission' => 'featureAdmin'],
                    ['key' => 'auth',     'label' => $t('ui.sidebar.auth_providers',    'Auth providers'), 'description' => $d('ui.dashboard.auth_providers_help', 'Configure external sign-in providers.'),     'href' => $base.'admin/auth-providers/',   'icon' => $svg('key'),      'permission' => 'authExt'],
                    ['key' => 'ai',       'label' => $t('ui.sidebar.ai_settings',       'AI settings'),    'description' => $d('ui.dashboard.ai_settings_help', 'Configure AI provider credentials.'),        'href' => $base.'admin/ai-settings/',      'icon' => $svg('sparkles'), 'permission' => 'admin'],
                    ['key' => 'firebase', 'label' => $t('ui.sidebar.firebase_settings', 'Firebase'),       'description' => $d('ui.dashboard.firebase_settings_help', 'Configure Firebase integration.'),       'href' => $base.'admin/firebase-settings/','icon' => $svg('cog'),      'permission' => 'settingAdmin'],
                    ['key' => 'env',      'label' => $t('ui.sidebar.env_settings',      'ENV settings'),  'description' => $d('ui.dashboard.env_settings_help', 'Edit values that historically lived in .env.'),'href' => $base.'admin/env-settings/',     'icon' => $svg('cog'),      'permission' => 'settingAdmin'],
                    ['key' => 'password', 'label' => $t('ui.sidebar.password',          'Password'),       'description' => $d('ui.dashboard.password_help', 'Change your sign-in credentials.'),            'href' => $base.'start/password/',         'icon' => $svg('key'),      'permission' => null],
                ]),
            ],
        ];

        return array_values(array_filter($groups, fn($g) => count($g['items']) > 0));
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
            ['label' => __('ui.nav.home', [], null, 'Home'), 'href' => $this->basePath()],
        ];
        if ($this->matter !== '' && $this->matter !== 'start') {
            $crumbs[] = ['label' => $title];
        }
        return $crumbs;
    }
}
