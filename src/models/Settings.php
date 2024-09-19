<?php

namespace madebyraygun\blockloader\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public string $blocksNamespace = '';

    // set defaults
    public function init(): void
    {
        parent::init();
        $this->blocksNamespace = 'modules/blocks';
    }


    public function rules(): array
    {
        return [
            ['blocksNamespace', 'required'],
            ['blocksNamespace', 'string']
        ];
    }
}
