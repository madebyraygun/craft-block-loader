<?php

namespace madebyraygun\blockloader\base;

class ContextBlockSettings
{
    private string $id;
    public string $blockHandle;
    public string $fieldHandle;
    public bool $cacheable;
    public array $eagerFields;

    public function __construct()
    {
        $this->id = uniqid();
        $this->fieldHandle = '';
        $this->blockHandle = '';
        $this->eagerFields = [];
        $this->cacheable = false;
    }

    /**
     * Set the field handle.
     * This should match the handle for the entry field ($entry->type->handle).
     * @param string $fieldHandle
     * @return self
     */
    public function fieldHandle(string $fieldHandle): self
    {
        $this->fieldHandle = $fieldHandle;
        return $this;
    }

    /**
     * Set the block handle.
     * This is the handle used to id the block in the template.
     * @param string $blockHandle
     * @return self
     */
    public function blockHandle(string $blockHandle): self
    {
        $this->blockHandle = $blockHandle;
        return $this;
    }

    /**
     * Set eager fields for the block.
     * See https://craftcms.com/docs/4.x/dev/eager-loading-elements.html
     * @param array|string[] $eagerFields
     * @return self
     */
    public function eagerFields(array $eagerFields): self
    {
        $this->eagerFields = $eagerFields;
        return $this;
    }

    /**
     * Set whether the block is cacheable.
     * @param bool $cacheable
     * @return self
     */
    public function cacheable(bool $cacheable): self
    {
        $this->cacheable = $cacheable;
        return $this;
    }
}
