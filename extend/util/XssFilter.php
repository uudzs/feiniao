<?php

declare(strict_types=1);

namespace util;

use HTMLPurifier;
use HTMLPurifier_Config;

class XssFilter
{
    protected static $instance;

    public static function purify(string $dirtyHtml): string
    {
        if (!self::$instance) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
            self::$instance = new HTMLPurifier($config);
        }
        return self::$instance->purify($dirtyHtml);
    }
}
