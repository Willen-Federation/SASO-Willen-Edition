<?php
namespace saso\util;

final class CSRFtoken
{
    public static function salting(string $salt): string
    {
        return hash('sha256', $salt.($_SESSION['id']??''));
    }
}