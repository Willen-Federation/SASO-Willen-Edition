<?php

declare(strict_types=1);

/**
 * Mobile-app configuration.
 *
 * The redirect_uri_allowlist controls which `?redirect_uri=…` values the
 * `/m/setup` and `/m/issue-pairing` endpoints will honour. A wildcard `:*`
 * port lets the Flutter web preview pick any port. Custom URL schemes
 * (e.g. `jp.willen.saso://callback`) must be exact matches.
 *
 * Environment override: MOBILE_REDIRECT_URI_ALLOWLIST (comma-separated)
 * is appended to whatever this file returns.
 */
return [
    'redirect_uri_allowlist' => [
        'jp.willen.saso://callback',
        'http://localhost:*',
        'http://127.0.0.1:*',
    ],
];
