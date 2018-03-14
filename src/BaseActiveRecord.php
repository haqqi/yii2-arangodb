<?php

namespace haqqi\arangodb;

use ArangoDBClient\ClientException;
use ArangoDBClient\Document;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\db\ActiveRecordInterface;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

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

    public static function collectionName()
    {
        return '`%' . Inflector::camel2id(StringHelper::basename(get_called_class()), '_') . '`';
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

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        // just initiate document if it is null
        if ($this->_document === null) {
            $this->_document = new Document();
        }
    }

    public function getPrimaryKey($asArray = false)
    {
        return $this->_document->getId();
    }

    public function hasAttribute($name)
    {
        return !\is_null($this->_document->get($name));
    }

    public function getAttribute($name)
    {
        return $this->_document->get($name);
    }

    public function setAttribute($name, $value)
    {
        try {
            $this->_document->set($name, $value);
        } catch (ClientException $e) {
            throw new InvalidArgumentException(\get_class($this) . ' has failed to set attribute.');
        }
    }
}
