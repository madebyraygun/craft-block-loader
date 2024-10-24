<?php

namespace madebyraygun\blockloader\base;

use craft\elements\Entry;
use Illuminate\Support\Collection;

class ContextCacheSet
{
    private Entry $entry;
    private Collection $cache;
    private Collection $descriptors;
    private Collection $instanceIds;
    private string $fieldHandle;
    private bool $dirty = false;

    public function __construct(Entry $entry, string $fieldHandle)
    {
        $this->entry = $entry;
        $this->fieldHandle = $fieldHandle;
        $this->instanceIds = collect([]);
    }

    public function hasId(string $id): bool
    {
        return $this->descriptors->has($id);
    }

    public function set(ContextDescriptor $contextDescriptor)
    {
        if ($contextDescriptor->cacheable) {
            $id = spl_object_id($contextDescriptor);
            if (!$this->instanceIds->contains($id)) {
                $this->dirty = true;
                $this->descriptors->put($contextDescriptor->id, $contextDescriptor);
            }
        }
    }

    public function add(Collection $newDescriptors)
    {
        foreach ($newDescriptors as $descriptor) {
            $this->set($descriptor);
        }
    }

    public function all(): Collection
    {
        return $this->descriptors;
    }

    public function getEntryIds(): Collection
    {
        // filter out descriptors with an id starting with markup:
        return $this->descriptors->pluck('id')->filter(fn($id) => !str_starts_with($id, 'markup:'));
    }

    public function read(): void
    {
        $this->cache = ContextCache::get($this->entry) ?? collect([]);
        $this->descriptors = collect($this->cache->get($this->fieldHandle) ?? []);
        $this->instanceIds = $this->descriptors->map(fn($d) => spl_object_id($d));
        $this->dirty = false;
    }

    public function write(): void
    {
        if (!$this->dirty) {
            return;
        }
        $this->cache->put($this->fieldHandle, $this->descriptors);
        ContextCache::set($this->entry, $this->cache);
        $this->dirty = false;
    }
}
