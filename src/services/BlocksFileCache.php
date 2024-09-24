<?php

namespace madebyraygun\blockloader\services;

use Craft;
use yii\caching\FileCache;
use craft\helpers\FileHelper;

class BlocksFileCache extends FileCache
{
    public function init(): void
    {
        parent::init();
        $this->cachePath = Craft::getAlias('@runtime/cache_blocks');
    }

    public function getRelativePath(): string
    {
        return FileHelper::relativePath($this->cachePath, Craft::getAlias('@root'));
    }
}
