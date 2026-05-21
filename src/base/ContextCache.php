<?php

namespace madebyraygun\blockloader\base;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use Illuminate\Support\Collection;
use madebyraygun\blockloader\Plugin;
use yii\base\Application;
use yii\base\Event;

class ContextCache
{
    private static $CACHE = null;

    /**
     * Elements queued for deferred cache invalidation.
     * Processed once at end of request instead of per-element.
     */
    private static array $pendingClears = [];
    private static array $pendingRelationElements = [];
    private static bool $deferredHandlerRegistered = false;

    private static function getKey(Element $element): string
    {
        return strval($element->id) . '-' . strval($element->siteId);
    }

    public static function set(Entry $entry, Collection $descriptors): void
    {
        $cacheEnabled = Plugin::$plugin->getSettings()['enableCaching'];
        if (Craft::$app->request->isPreview || !$cacheEnabled) {
            return;
        }
        $key = static::getKey($entry);
        try {
            $serialized = serialize($descriptors->toArray());
        } catch (\Exception $e) {
            Craft::warning("Block loader cache skipped for entry {$entry->id}: {$e->getMessage()}", __METHOD__);
            return;
        }
        Plugin::$plugin->cache->set($key, $serialized);
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

    public static function clear(Element $element): void
    {
        $key = static::getKey($element);
        Plugin::$plugin->cache->delete($key);
    }

    public static function filterCacheableDescriptors(Collection $descriptors): Collection
    {
        return $descriptors->filter(fn($descriptor) => $descriptor->cacheable);
    }

    /**
     * Returns true when the save can't affect the live block-loader cache and
     * invalidation should be skipped entirely.
     *
     * Pure function of element state. Called at the top of every
     * EVENT_AFTER_SAVE listener; no side effects.
     */
    public static function shouldSkipInvalidation(Element $element): bool
    {
        if (ElementHelper::isDraftOrRevision($element)) {
            return true;
        }
        if ($element->propagating) {
            return true;
        }
        if ($element->resaving) {
            return true;
        }
        if ($element instanceof Asset && $element->getScenario() === Asset::SCENARIO_INDEX) {
            return true;
        }
        return false;
    }

    public static function clearRelations(mixed $element): void
    {
        $entries = Entry::find()
            ->relatedTo($element)
            ->site('*')
            ->all();

        $cleanedIds = [];

        foreach ($entries as $entry) {
            // skip if entry was already cleaned
            if (in_array($entry->id, $cleanedIds)) {
                continue;
            }
            $cleanedIds[] = $entry->id;
            static::clear($entry);
            if ($entry->owner && !in_array($entry->ownerId, $cleanedIds)) {
                static::clear($entry->owner);
            }
        }
    }

    /**
     * Register the end-of-request handler that processes all deferred invalidations.
     */
    private static function ensureDeferredHandler(): void
    {
        if (static::$deferredHandlerRegistered) {
            return;
        }
        static::$deferredHandlerRegistered = true;

        Event::on(Application::class, Application::EVENT_AFTER_REQUEST, function() {
            static::processDeferredInvalidation();
        });
    }

    /**
     * Queue an element for deferred direct cache clear.
     */
    private static function queueClear(Element $element): void
    {
        $key = static::getKey($element);
        static::$pendingClears[$key] = $element;
    }

    /**
     * Queue an element whose relations need deferred invalidation.
     */
    private static function queueRelationClear(Element $element): void
    {
        $key = $element->id . '-' . ($element instanceof Entry ? 'entry' : 'asset');
        static::$pendingRelationElements[$key] = $element;
    }

    /**
     * Process all deferred cache invalidations in a single pass.
     */
    private static function processDeferredInvalidation(): void
    {
        try {
            // 1. Clear directly queued elements
            foreach (static::$pendingClears as $element) {
                static::clear($element);
            }

            // 2. Clear cache for all entries related to queued elements
            if (!empty(static::$pendingRelationElements)) {
                $elements = array_values(static::$pendingRelationElements);
                $chunks = array_chunk($elements, 100);
                foreach ($chunks as $chunk) {
                    $entries = Entry::find()
                        ->relatedTo($chunk)
                        ->site('*')
                        ->all();

                    $cleanedIds = [];
                    foreach ($entries as $entry) {
                        if (in_array($entry->id, $cleanedIds)) {
                            continue;
                        }
                        $cleanedIds[] = $entry->id;
                        static::clear($entry);
                        if ($entry->owner && !in_array($entry->ownerId, $cleanedIds)) {
                            $cleanedIds[] = $entry->ownerId;
                            static::clear($entry->owner);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Craft::error(
                'Block loader deferred cache invalidation failed: ' . $e->getMessage(),
                __METHOD__
            );
        } finally {
            static::$pendingClears = [];
            static::$pendingRelationElements = [];
        }
    }

    public static function attachEventHandlers(): void
    {
        // Console/queue: use immediate invalidation (EVENT_AFTER_REQUEST doesn't fire)
        if (Craft::$app->request->isConsoleRequest) {
            Event::on(Asset::class, Asset::EVENT_AFTER_SAVE, function(ModelEvent $event) {
                static::clearRelations($event->sender);
            });
            Event::on(Entry::class, Entry::EVENT_AFTER_SAVE, function(ModelEvent $event) {
                static::clear($event->sender);
                static::clearRelations($event->sender);
            });
            return;
        }

        // Web requests: defer invalidation to end of request
        static::ensureDeferredHandler();

        Event::on(Asset::class, Asset::EVENT_AFTER_SAVE, function(ModelEvent $event) {
            static::queueRelationClear($event->sender);
        });

        Event::on(Entry::class, Entry::EVENT_AFTER_SAVE, function(ModelEvent $event) {
            static::queueClear($event->sender);
            static::queueRelationClear($event->sender);
        });
    }
}
