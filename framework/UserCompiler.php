<?php
namespace saso\framework;

use saso\util\CSRFtoken;
use saso\util\Mapper;
use saso\util\PathExploder;
use saso\util\Sanitizer;

final class UserCompiler
{
    private array $pair;
    private array $post;
    public function __construct(
        string $pathtoxin,
        array $postoxin,
        private array $config,
        private bool $authed,
        private \DateTime $now,
    )
    {
        $this->pair = self::pathToPair($pathtoxin, $this->config['programDir']??'');
        $this->post = Sanitizer::execMap($postoxin);
        if(!empty($this->post) && $authed) {
            CSRFtoken::verify((string)($this->post['csrftoken']??'')) or die('invalid csrftoken.');
        }
    }
    public static function pathToPair(string $path, string $programDir): array
    {
        return array_chunk(
            PathExploder::exec(
                $programDir, Sanitizer::execString($path)
            ),
            2
        );
    }
    public function request(): array
    {
        return array_slice($this->pair, 0, 1)[0]??[];
    }
    public function query(): array
    {
        return Mapper::exec(array_slice(
                $this->pair,
                1
        ));
    }
    public function post(): array
    {
        return $this->post;
    }
    public function config(): array
    {
        return $this->config;
    }
    public function authed(): bool
    {
        return $this->authed;
    }
    public function now(): \DateTime
    {
        return $this->now;
    }
}