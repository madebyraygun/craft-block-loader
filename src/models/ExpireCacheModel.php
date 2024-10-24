<?php

namespace madebyraygun\blockloader\models;

use craft\base\Model;

class ExpireCacheModel extends Model
{
    public int $entryId;

    public function rules(): array
    {
        return [
            [['entryId'], 'required'],
        ];
    }
}
