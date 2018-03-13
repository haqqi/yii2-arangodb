<?php

namespace haqqi\tests\arangodb;

use haqqi\tests\arangodb\data\bridge\ActiveRecord;

class ActiveRecordTest extends TestCase
{
    public function testPrimaryKey()
    {
        $this->assertTrue(ActiveRecord::isPrimaryKey(['_id']));
        $this->assertFalse(ActiveRecord::isPrimaryKey(['_key']));
    }
}
