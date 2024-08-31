<?php

namespace madebyraygun\blockloader\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public string $blocksPath = '';

    public string $matrixHandle = 'blocks';

    public string $hookName = 'blocks';

    public string $globalContextHandle = 'blocks';

    // set defaults
    public function init(): void
    {
        parent::init();
        $this->blocksPath = Craft::getAlias('@root') . '/modules/blocks';
    }


    public function rules(): array
    {
        return [
            ['blocksPath', 'required'],
            ['blocksPath', 'string'],
            ['matrixHandle', 'required'],
            ['matrixHandle', 'string'],
            ['hookName', 'required'],
            ['hookName', 'string'],
            ['globalContextHandle', 'required'],
            ['globalContextHandle', 'string'],
        ];
    }
}
