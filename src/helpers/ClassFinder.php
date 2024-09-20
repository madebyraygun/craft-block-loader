<?php

namespace madebyraygun\blockloader\helpers;

use Craft;
use Composer\Autoload\ClassLoader;


class ClassFinder
{
    public static function loadNamespace(string $namespace): array
    {
        return self::getClassesFromAutoload($namespace);
    }

    private static function getClassesFromAutoload(string $namespace): array
    {
        if (empty($namespace)) {
            return [];
        }

        // require modules/blocks/CustomBlock.php
        $path = Craft::getAlias('@root' . '/modules/blocks/CustomBlock.php');
        if (file_exists($path)) {
            require_once $path;
        }
        $cls = get_declared_classes();
        // find classes that start with the namespace
        $test_classes = array_filter($cls, function($cls) use ($namespace) {
            return strpos($cls, $namespace) === 0;
        });

        $spl_af = spl_autoload_functions();
        $classLoader = null;
        foreach ($spl_af as $func) {
            if (is_array($func) && $func[0] instanceof ClassLoader) {
                $classLoader = $func[0];
                break;
            }
        }
        if (empty($classLoader)) {
            return [];
        }
        $classMap = $classLoader->getClassMap();
        $arr = array_filter($classMap, function($key) use ($namespace) {
            // starts with namespace
            return strpos($key, $namespace) === 0;
        }, ARRAY_FILTER_USE_KEY);
        return array_keys($arr);
    }
}
