<?php

namespace madebyraygun\blockloader\web\twig;

use Craft;
use yii\base\Behavior;
use madebyraygun\blockloader\base\BlocksProvider;
use Illuminate\Support\Collection;

class BlocksLoader extends Behavior
{
    public function blocksFromField(string $fieldHandle): Collection
    {
        if (!$this->owner->getFieldValue($fieldHandle)) {
            return collect([]);
        }
        return BlocksProvider::extractBlockDescriptors($this->owner, $fieldHandle);
    }
}
