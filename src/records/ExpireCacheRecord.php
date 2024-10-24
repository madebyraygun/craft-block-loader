<?php

namespace madebyraygun\blockloader\records;

use craft\db\ActiveRecord;

class ExpireCacheRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%blockloader_cache_expired}}';
    }
}
