<?php

namespace madebyraygun\blockloader\migrations;

use craft\db\Migration;
use madebyraygun\blockloader\records\ExpireCacheRecord;

class Install extends Migration
{
    public function safeUp()
    {
        $this->createTables();
        return true;
    }

    public function safeDown()
    {
        $this->dropTableIfExists(ExpireCacheRecord::tableName());
        return true;
    }

    private function createTables()
    {
        $this->archiveTableIfExists(ExpireCacheRecord::tableName());
        $this->createTable(ExpireCacheRecord::tableName(), [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer()->notNull(),
            'dateCleared' => $this->dateTime()->notNull(),
        ]);
    }
}
