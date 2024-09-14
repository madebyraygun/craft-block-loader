<?php

namespace madebyraygun\blockloader\base;

use craft\elements\Entry;
use craft\helpers\StringHelper;

abstract class ContextBlock
{
    public ?Entry $entry;
    public ContextBlockSettings $settings;

    public function getContext(Entry $block): array
    {
        return [];
    }

    public function getMarkupContext(string $markup): string
    {
        return $markup;
    }

    public function setSettings(): void
    {
        // $this->settings
    }

    public function __construct(Entry $entry = null)
    {
        $handle = $this->getDefaultHandle();
        $this->entry = $entry;
        $this->settings = (new ContextBlockSettings())
            ->blockHandle(lcfirst($handle))
            ->contextHandle(StringHelper::toKebabCase($handle))
            ->cacheable(true);
        $this->setSettings();
    }

    public function getDefaultHandle(): string
    {
        $ref = new \ReflectionClass($this);
        $className = $ref->getShortName();
        return str_replace('Block', '', $className);
    }

    // public function __clone()
    // {
    //     $oldSettings = $this->settings;
    //     $this->settings = new ContextBlockSettings();
    //     $this->settings->copy($oldSettings);
    // }
}
