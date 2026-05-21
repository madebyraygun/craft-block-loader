<?php

namespace madebyraygun\blockloader\tests\Unit;

use craft\elements\Asset;
use craft\elements\Entry;
use madebyraygun\blockloader\base\ContextCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContextCache::class)]
final class ContextCacheTest extends TestCase
{
    public function testSkipsDrafts(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(true);
        $entry->method('getIsRevision')->willReturn(false);

        self::assertTrue(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testSkipsRevisions(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(true);

        self::assertTrue(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testSkipsPropagatingSaves(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(false);
        $entry->propagating = true;

        self::assertTrue(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testSkipsBulkResaves(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(false);
        $entry->propagating = false;
        $entry->resaving = true;

        self::assertTrue(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testSkipsAssetIndexerPass(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getIsDraft')->willReturn(false);
        $asset->method('getIsRevision')->willReturn(false);
        $asset->method('getScenario')->willReturn(Asset::SCENARIO_INDEX);
        $asset->propagating = false;
        $asset->resaving = false;

        self::assertTrue(ContextCache::shouldSkipInvalidation($asset));
    }

    public function testSkipsDisabledEntryWhenNoTransition(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(false);
        $entry->method('getStatus')->willReturn(Entry::STATUS_DISABLED);
        $entry->method('getDirtyAttributes')->willReturn(['title']);
        $entry->propagating = false;
        $entry->resaving = false;

        self::assertTrue(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testDoesNotSkipDisabledEntryWhenEnabledIsDirty(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(false);
        $entry->method('getStatus')->willReturn(Entry::STATUS_DISABLED);
        $entry->method('getDirtyAttributes')->willReturn(['enabled']);
        $entry->propagating = false;
        $entry->resaving = false;

        self::assertFalse(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testDoesNotSkipDisabledEntryWhenPostDateIsDirty(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(false);
        $entry->method('getStatus')->willReturn(Entry::STATUS_PENDING);
        $entry->method('getDirtyAttributes')->willReturn(['postDate']);
        $entry->propagating = false;
        $entry->resaving = false;

        self::assertFalse(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testDoesNotSkipDisabledEntryWhenExpiryDateIsDirty(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(false);
        $entry->method('getStatus')->willReturn(Entry::STATUS_EXPIRED);
        $entry->method('getDirtyAttributes')->willReturn(['expiryDate']);
        $entry->propagating = false;
        $entry->resaving = false;

        self::assertFalse(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testSkipsPendingEntryWhenNoTransition(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(false);
        $entry->method('getStatus')->willReturn(Entry::STATUS_PENDING);
        $entry->method('getDirtyAttributes')->willReturn([]);
        $entry->propagating = false;
        $entry->resaving = false;

        self::assertTrue(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testDoesNotSkipLiveEntry(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(false);
        $entry->method('getStatus')->willReturn(Entry::STATUS_LIVE);
        $entry->method('getDirtyAttributes')->willReturn(['title']);
        $entry->propagating = false;
        $entry->resaving = false;

        self::assertFalse(ContextCache::shouldSkipInvalidation($entry));
    }

    public function testDoesNotSkipLiveEntryEvenWithTransitionAttrDirty(): void
    {
        $entry = $this->createMock(Entry::class);
        $entry->method('getIsDraft')->willReturn(false);
        $entry->method('getIsRevision')->willReturn(false);
        $entry->method('getStatus')->willReturn(Entry::STATUS_LIVE);
        $entry->method('getDirtyAttributes')->willReturn(['expiryDate']);
        $entry->propagating = false;
        $entry->resaving = false;

        self::assertFalse(ContextCache::shouldSkipInvalidation($entry));
    }
}
