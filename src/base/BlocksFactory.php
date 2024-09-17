<?php

namespace madebyraygun\blockloader\base;

use Illuminate\Support\Collection;
use craft\elements\Entry;

class BlocksFactory
{

    public static array $classes = [];
    private static ?Collection $contextSettings = null;

    public static function init(array $classes): void {
        self::$classes = $classes;
    }

    public static function create(string $handle): ?ContextBlock
    {
        $class = self::findHandlerClass($handle);
        if (!$class) {
            return null;
        }
        return new $class;
    }

    public static function createFromEntry(Entry $entry): ?ContextBlock
    {
        $contextBlock = self::create($entry->type->handle);
        if (!$contextBlock) {
            return null;
        }
        $contextBlock->setEntry($entry);
        return $contextBlock;
    }

    public static function getContextSettings(): Collection {
        if (self::$contextSettings === null) {
            $result = collect([]);
            foreach (self::$classes as $cls) {
                if (is_subclass_of($cls, ContextBlock::class)) {
                    $ctxBlock = new $cls;
                    $result->push([
                        'class' => $cls,
                        'settings' => $ctxBlock->settings
                    ]);
                }
            }
            self::$contextSettings = $result;
        }
        return self::$contextSettings;
    }

    public static function findHandlerClass(string $handle): ?string {
        $settings = self::getContextSettings()->firstWhere('settings.fieldHandle', $handle);
        return $settings['class'] ?? null;
    }
}
