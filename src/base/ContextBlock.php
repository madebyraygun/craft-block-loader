<?php

namespace madebyraygun\blockloader\base;

use craft\elements\Entry;
use craft\helpers\StringHelper;

abstract class ContextBlock
{
    public ContextBlockSettings $settings;
    public Entry $entry;

    abstract public function getContext(Entry $block): array;

    protected function onInit(ContextBlockSettings $settings): void
    {
        // override this method to set block settings
    }

    public function __construct(Entry $entry)
    {
        $this->entry = $entry;
        $this->settings = new ContextBlockSettings();
        $handle = $this->getDefaultHandle();
        $this->settings
            ->blockHandle(lcfirst($handle))
            ->contextHandle(StringHelper::toKebabCase($handle))
            ->cacheable(true);
        $this->onInit($this->settings);
    }

    public function getDefaultHandle(): string
    {
        $ref = new \ReflectionClass($this);
        $className = $ref->getShortName();
        return str_replace('Block', '', $className);
    }

    public function __clone()
    {
        $oldSettings = $this->settings;
        $this->settings = new ContextBlockSettings();
        $this->settings->copy($oldSettings);
    }
}
