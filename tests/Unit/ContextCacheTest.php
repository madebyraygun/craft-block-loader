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
}
