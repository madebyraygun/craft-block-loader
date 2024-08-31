<?php

namespace madebyraygun\blockloader\base;

use madebyraygun\blockloader\Plugin;

class ContextBlockSettings
{
    private string $id;
    public string $contextHandle;
    public string $matrixHandle;
    public string $blockHandle;
    public bool $cacheable;
    public array $eagerFields;

    public function __construct()
    {
        $settings = Plugin::getInstance()->getSettings();
        $this->id = uniqid();
        $this->blockHandle = '';
        $this->contextHandle = $settings['globalContextHandle'];
        $this->matrixHandle = $settings['matrixHandle'];
        $this->eagerFields = [];
        $this->cacheable = false;
    }

    /**
     * Set the block handle.
     * This should match the handle for the block type inside the matrix field.
     * @param string $blockHandle
     * @return self
     */
    public function blockHandle(string $blockHandle): self
    {
        $this->blockHandle = $blockHandle;
        return $this;
    }

    /**
     * Set the context handle for the block.
     * This is the handle used to access the block in the template.
     * @param string $contextHandle
     * @return self
     */
    public function contextHandle(string $contextHandle): self
    {
        $this->contextHandle = $contextHandle;
        return $this;
    }

    /**
     * Set the matrix handle for the block.
     * This should match the handle of the matrix field in the entry.
     * @param string $matrixHandle
     * @return self
     */
    public function matrixHandle(string $matrixHandle): self
    {
        $this->matrixHandle = $matrixHandle;
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

    /**
     * Copy settings from another ContextBlockSettings instance.
     * @param ContextBlockSettings $settings
     * @return self
     */
    public function copy(ContextBlockSettings $settings): self
    {
        $this->blockHandle = $settings->blockHandle;
        $this->contextHandle = $settings->contextHandle;
        $this->matrixHandle = $settings->matrixHandle;
        $this->eagerFields = $settings->eagerFields;
        $this->cacheable = $settings->cacheable;
        return $this;
    }
}
