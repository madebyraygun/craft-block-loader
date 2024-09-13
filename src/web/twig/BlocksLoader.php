<?php

namespace madebyraygun\blockloader\web\twig;

use Craft;
use yii\base\Behavior;
use madebyraygun\blockloader\base\BlocksProvider;
use Illuminate\Support\Collection;

class BlocksLoader extends Behavior
{
    public function blocksFromField($field): array
    {
        $fields = collect($this->owner->fieldValues);
        $fieldHandle = $fields->search($field);
        return BlocksProvider::extractBlockDescriptors($this->owner, $fieldHandle);
    }
}
