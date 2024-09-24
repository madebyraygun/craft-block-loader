<?php

namespace madebyraygun\blockloader\base;

use Craft;
use yii\base\Event;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\events\ModelEvent;
use Illuminate\Support\Collection;
use madebyraygun\blockloader\Plugin;

class ContextCache
{
    private static $CACHE = null;

    private static function getKey(Entry $entry): string
    {
        return strval($entry->id);
    }

    public static function set(Entry $entry, Collection $descriptors): void
    {
        $cacheEnabled = Plugin::$plugin->getSettings()['enableCaching'];
        if (Craft::$app->request->isPreview || !$cacheEnabled) {
            return;
        }
        $key = static::getKey($entry);
        Plugin::$plugin->cache->set($key, serialize($descriptors->toArray()));
    }

    public static function get(Entry $entry): ?Collection
    {
        $cacheEnabled = Plugin::$plugin->getSettings()['enableCaching'];
        if (!Craft::$app->request->isPreview && static::$CACHE === null && $cacheEnabled) {
            $key = static::getKey($entry);
            $content = Plugin::$plugin->cache->get($key);
            if (!empty($content)) {
                static::$CACHE = collect(unserialize($content));
            }
        }
        return static::$CACHE;
    }

    public static function clear(Entry $entry): void
    {
        $key = static::getKey($entry);
        Plugin::$plugin->cache->delete($key);
    }

    public static function filterCacheableDescriptors(Collection $descriptors): Collection
    {
        return $descriptors->filter(fn($descriptor) => $descriptor->cacheable);
    }

    private static function clearRelations(mixed $element): void
    {
        $entries = Entry::find()
            ->relatedTo($element)
            ->all();

        $cleanedIds = [];

        foreach ($entries as $entry) {
            // skip if entry was already cleaned
            if (in_array($entry->id, $cleanedIds)) {
                continue;
            }
            $cleanedIds[] = $entry->id;
            static::clear($entry);
        }
    }

    public static function attachEventHandlers(): void
    {
        // Handle Asset saves
        Event::on(Asset::class, Asset::EVENT_AFTER_SAVE, function(ModelEvent $event) {
            $asset = $event->sender;
            static::clearRelations($asset);
        });

        // Handle Entry saves
        Event::on(Entry::class, Entry::EVENT_AFTER_SAVE, function(ModelEvent $event) {
            $entry = $event->sender;
            static::clear($entry);
            static::clearRelations($entry);
        });
    }
}
