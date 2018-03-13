<?php

namespace haqqi\arangodb;

use ArangoDBClient\Document;
use yii\base\Model;
use yii\db\ActiveRecordInterface;

abstract class BaseActiveRecord extends Model implements ActiveRecordInterface
{
    /** @var Document to hold attribute of the active record */
    private $_document;

    /**
     * @since 2018-03-13 13:04:14
     * @return Connection|null
     */
    public static function getDb()
    {
        return \Yii::$app->arangodb;
    }

    public static function primaryKey()
    {
        return ['_id'];
    }

    public static function isPrimaryKey($keys)
    {
        $pks = static::primaryKey();
        if (count($keys) === count($pks)) {
            return count(array_intersect($keys, $pks)) === count($pks);
        }

        return false;
    }
}
