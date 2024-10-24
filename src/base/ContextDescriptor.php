<?php

namespace madebyraygun\blockloader\base;

class ContextDescriptor
{
    public string $id;
    public int $order;
    public bool $cacheable;
    public string $fieldHandle;
    public string $templateHandle;
    public array $context;

    public function __construct(
        string $id,
        string $fieldHandle,
        string $templateHandle,
        int $order,
        bool $cacheable,
        array $context,
    ) {
        $this->id = $id;
        $this->fieldHandle = $fieldHandle;
        $this->templateHandle = $templateHandle;
        $this->order = $order;
        $this->cacheable = $cacheable;
        $this->context = $context;
    }
}
