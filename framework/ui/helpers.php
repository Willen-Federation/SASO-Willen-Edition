<?php

declare(strict_types=1);

/*
 * Global UI partial helper.
 *
 * Auto-registered via Composer's `files` autoload. Templates call
 * `ui('formField', ['name'=>'foo', 'label'=>__('item.name')])` and the
 * helper requires the corresponding partial under
 * `root/template/_components/{name}.php`, with the args extracted
 * into local scope.
 *
 * The helper does NOT echo anything by itself — partials are responsible
 * for emitting their own markup. Args names collide with local variables
 * inside the partial, so partials should treat all keys of `$args` as
 * pre-bound locals (e.g. `$label`, `$name`, etc.).
 */

if (!function_exists('ui')) {
    /**
     * Render a component partial.
     *
     * @param string $name  Component file name without `.php`, relative to `root/template/_components/`.
     * @param array<string, mixed> $args  Variables made available to the partial.
     */
    function ui(string $__ui_name, array $__ui_args = []): void
    {
        // Project root is two directories up from framework/ui/
        $__ui_partial = dirname(__DIR__, 2).'/root/template/_components/'.$__ui_name.'.php';
        if (!is_file($__ui_partial)) {
            throw new RuntimeException("UI partial not found: {$__ui_name} (looked in {$__ui_partial})");
        }
        // Note: parameter names are prefixed `$__ui_*` so extract() can safely
        // populate $name, $label, etc. from $__ui_args without colliding with
        // this function's locals. EXTR_SKIP would otherwise refuse to set
        // `$name` because a local `$name` already exists.
        extract($__ui_args, EXTR_OVERWRITE);
        require $__ui_partial;
    }
}

if (!function_exists('ui_attr')) {
    /**
     * Escape an HTML attribute value. Defensive convenience used by the
     * partials to keep their bodies short and safe.
     */
    function ui_attr(string|int|float|bool|null $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        if ($value === true) {
            return 'true';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('ui_text')) {
    /**
     * Escape a text node value.
     */
    function ui_text(string|int|float|null $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
