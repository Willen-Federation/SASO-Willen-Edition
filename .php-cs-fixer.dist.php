<?php
declare(strict_types=1);

/*
 * PSR-12 + a small set of project conventions, applied only to files this
 * fork rewrites or owns. Legacy directories are deliberately excluded — they
 * migrate to src/ across M3-M4 and will start picking up these rules once the
 * Strangler Fig sweep relocates them.
 */

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->append([
        __DIR__.'/util/EnvLoader.php',
        __DIR__.'/util/UploadValidator.php',
        __DIR__.'/util/CSRFtoken.php',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12'                              => true,
        'array_syntax'                        => ['syntax' => 'short'],
        'no_unused_imports'                   => true,
        'ordered_imports'                     => ['sort_algorithm' => 'alpha'],
        'single_quote'                        => true,
        'trailing_comma_in_multiline'         => true,
        'no_trailing_whitespace_in_comment'   => true,
        'no_whitespace_in_blank_line'         => true,
        'no_extra_blank_lines'                => true,
        'cast_spaces'                         => ['space' => 'single'],
        'concat_space'                        => ['spacing' => 'none'],
        'native_function_invocation'          => false,
        'phpdoc_align'                        => ['align' => 'left'],
        'phpdoc_separation'                   => true,
        'phpdoc_trim'                         => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache');
