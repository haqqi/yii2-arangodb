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

    /**
     * Only _id is the primary key
     *
     * @since 2018-03-13 13:49:42
     * @return array|string[]
     */
    public static function primaryKey()
    {
        return ['_id'];
    }

    /**
     * Only _id is the primary key
     *
     * @since 2018-03-13 13:52:18
     *
     * @param array $keys
     *
     * @return bool
     */
    public static function isPrimaryKey($keys)
    {
        if (\count($keys) !== 1) {
            return false;
        }

        $pks = self::primaryKey();

        if ($keys[0] != $pks[0]) {
            return false;
        }

        return true;
    }

    public function getPrimaryKey($asArray = false)
    {
        return $this->_document->getId();
    }
}
