<?php
namespace saso\root;

use saso\framework\DIContainer;
use saso\framework\Flow;
use Saso\Infrastructure\Translation\TranslatorRegistry;

final class RootDIContainer implements DIContainer
{
    use Flow;
    public function __construct(
        private string $matter,
        private String $action,
        private array $flow,
        private bool $authed,
    )
    {
    }
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        // Resolve locale from the registry. index.php binds the translator
        // before the legacy router runs; if for some reason it has not been
        // bound (e.g. unit-test direct construction), default to 'en'.
        $currentLocale    = TranslatorRegistry::isInitialised()
            ? TranslatorRegistry::get()->getLocale()
            : 'en';
        $supportedLocales = ['en', 'ja'];

        $this->ctrl = new RootController(
            $config,
            $this->authed,
            $this->matter,
            $this->action,
            $currentLocale,
            $supportedLocales,
        );
        $this->usecase = new RootUsecase(
            new RootPresenter(
                new RootView($inside),
            ),
        );
    }
}
