<?php

namespace madebyraygun\blockloader\helpers;

use Composer\Autoload\ClassLoader;
use Composer\ClassMapGenerator\ClassMapGenerator;

class ClassFinder
{
    private static ClassLoader $classLoader;

    public static function getClasses(string $namespace, bool $scanNewFiles): array
    {
        if (empty($namespace)) {
            return [];
        }
        $classes = self::getClassesFromAutoload($namespace);
        if ($scanNewFiles) {
            $fileClasses = self::getClassesFromFiles($namespace);
            $classes = array_unique(array_merge($fileClasses, $classes));
        }
        // remove invalid classes
        $classes = array_filter($classes, function($class) {
            try {
                return class_exists($class);
            } catch (\Throwable $e) {
                return false;
            }
        });
        return $classes;
    }

    private static function getClassLoader(): ClassLoader
    {
        if (!isset(self::$classLoader)) {
            $spl_af = spl_autoload_functions();
            $classLoader = null;
            foreach ($spl_af as $func) {
                if (is_array($func) && $func[0] instanceof ClassLoader) {
                    $classLoader = $func[0];
                    break;
                }
            }
            self::$classLoader = $classLoader;
        }
        return self::$classLoader;
    }

    private static function getClassesFromAutoload(string $namespace): array
    {
        $classLoader = self::getClassLoader();
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

    private static function getClassesFromFiles(string $namespace): array
    {
        $classLoader = self::getClassLoader();
        $psr4 = $classLoader->getPrefixesPsr4();
        // find the longest matching prefix for the namespace and fallback by removing the last part
        // until we find a match or reach the root.
        $path = '';
        $parts = explode('\\', $namespace);
        $len = count($parts);
        for ($i = $len; $i > 0; $i--) {
            $prefix = implode('\\', array_slice($parts, 0, $i)) . '\\';
            if (isset($psr4[$prefix])) {
                $path = $psr4[$prefix][0];
                break;
            }
        }
        return array_keys(ClassMapGenerator::createMap($path));
    }
}
