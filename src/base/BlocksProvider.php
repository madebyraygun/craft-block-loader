<?php

namespace madebyraygun\blockloader\base;

use Craft;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use madebyraygun\blockloader\Plugin;

class BlocksProvider
{
    private static $globalContextHandle = '';

    private static $hookName = '';

    private static function setHookName() : void
    {
        $settings = Plugin::getInstance()->getSettings();
        self::$hookName = $settings['hookName'];
    }

    private static function setGlobalContextHandle() : void
    {
        $settings = Plugin::getInstance()->getSettings();
        self::$globalContextHandle = $settings['globalContextHandle'];
    }

    public static function init(array $blockClasses): void
    {
        static::setHookName();
        ContextCache::attachEventHandlers();
        Craft::$app->view->hook(static::$hookName, function(array &$context) use ($blockClasses) {
            $entry = $context['entry'] ?? null;
            $cachedDescriptors = ContextCache::get($entry);
            $prototypeBlocks = static::getPrototypeBlocks($entry, $blockClasses);
            $newDescriptors = static::getNewDescriptors($entry, $prototypeBlocks, $cachedDescriptors);
            $descriptors = array_merge($newDescriptors, $cachedDescriptors);
            static::updateCacheIfNeeded($entry, $newDescriptors, $descriptors);
            static::setBlockDescriptors($context, $descriptors);
        });
    }

    /**
     * Update the cache if new descriptors were created.
     * @param Entry $entry the entry
     * @param array $newDescriptors the new descriptors
     * @param array $descriptors the descriptors
     * @return void
     */
    private static function updateCacheIfNeeded($entry, $newDescriptors, $descriptors): void
    {
        $newDescriptors = ContextCache::filterCacheableDescriptors($newDescriptors);
        if (!empty($newDescriptors)) {
            ContextCache::set($entry, $descriptors);
        }
    }

    /**
     * Get new block descriptors for the given entry.
     * @param Entry $entry the entry
     * @param array $prototypeBlocks the prototype blocks
     * @param array $cachedDescriptors the cached block descriptors
     */
    private static function getNewDescriptors($entry, $prototypeBlocks, $cachedDescriptors): array
    {
        $pendingBlockPrototypes = static::excludeCachedBlocks($cachedDescriptors, $prototypeBlocks);
        return static::createDescriptors($entry, $pendingBlockPrototypes);
    }

    /**
     * Filter out prototype blocks that are already cached.
     * @param array $descriptors the cached block descriptors
     * @param array $blocks the blocks to filter
     */
    public static function excludeCachedBlocks(array $descriptors, array $blocks): array
    {
        $result = $blocks;
        foreach ($descriptors as $descriptor) {
            $result = array_filter($result, function($block) use ($descriptor) {
                return $block->settings->blockHandle !== $descriptor->blockHandle;
            });
        }
        return $result;
    }

    /**
     * Build block descriptors for the given entry blocks.
     * This will build descriptors with the context of all the blocks in the entry.
     * @param Entry $entry the entry
     * @param array $blocks the blocks to build descriptors for
     */
    public static function createDescriptors($entry, $blocks): array
    {
        $result = [];
        $ctxQuery = new ContextQuery($entry, $blocks);
        foreach ($blocks as $block) {
            $matrixBlocks = $ctxQuery->findMatrixBlocks($block);
            $blockDescriptors = static::getBlockDescriptors($block, $matrixBlocks);
            $result = array_merge($blockDescriptors, $result);
        }
        return $result;
    }

    /**
     * Create prototype blocks for all available block classes.
     * Because settings can vary between blocks of the same type, each time a block is
     * paired with a matrix block, a new block instance is created. Thus keeping the settings separate.
     * @param Entry $entry the entry
     * @param array $blockClasses the block classes to create prototypes for
     */
    public static function getPrototypeBlocks(Entry $entry, array $blockClasses): array
    {
        return array_map(function($class) use ($entry) {
            return new $class($entry);
        }, $blockClasses);
    }

    /**
     * Creates block descriptors for the given block and matrix blocks.
     * @param ContextBlock $block the block
     * @param array $matrixBlocks the matrix blocks
     */
    private static function getBlockDescriptors(ContextBlock $block, array $matrixBlocks): array
    {
        return array_map(function(MatrixBlock $matrixBlock) use ($block) {
            return new ContextDescriptor($block, $matrixBlock);
        }, $matrixBlocks);
    }

    /**
     * Make blocks context available in the template.
     * @param array $context the context
     * @param array $descriptors the block descriptors
     */
    private static function setBlockDescriptors(array &$context, array $descriptors): void
    {
        static::setGlobalContextHandle();
        // clear empty blocks
        $descriptors = array_filter($descriptors, function($descriptor) {
            return !empty($descriptor);
        });
        // sort blocks
        usort($descriptors, function($a, $b) {
            return $a->order <=> $b->order;
        });
        // set blocks
        $context[static::$globalContextHandle] = $descriptors;
    }
}
