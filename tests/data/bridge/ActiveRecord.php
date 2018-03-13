<?php

namespace haqqi\tests\arangodb\data\bridge;

class ActiveRecord extends \haqqi\arangodb\ActiveRecord
{
    public static $db;

    public static function getDb()
    {
        return self::$db;
    }
}
