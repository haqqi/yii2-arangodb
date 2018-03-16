<?php

namespace haqqi\arangodb;

use ArangoDBClient\ClientException;
use ArangoDBClient\Document;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\db\ActiveRecordInterface;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * Class BaseActiveRecord
 * @package haqqi\arangodb
 *
 * @property-read $isNewRecord
 */
abstract class BaseActiveRecord extends Model implements ActiveRecordInterface
{
    /** @var Document to hold attribute of the active record */
    private $_document;
    /** @var array Related object */
    private $_related = [];

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
            $this->_document = new Document([
                '_validate' => true
            ]);
        }
    }

    public function getPrimaryKey($asArray = false)
    {
        return $this->_document->getId();
    }

    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (\Exception $e) {
            // if it catch general php exception, return the document getter
            // @todo: get the related first
            return $this->getAttribute($name);
        }
    }

    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (\Exception $e) {
            // @todo: set the related first
            $this->setAttribute($name, $value);
        }
    }

    public function __isset($name)
    {
        try {
            return $this->__get($name) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @since 2018-03-14 17:35:26
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        return $this->_document->get($name);
    }

    /**
     * @since 2018-03-14 17:35:32
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setAttribute($name, $value)
    {
        try {
            $this->_document->set($name, $value);
        } catch (ClientException $e) {
            throw new InvalidArgumentException('Value must be either boolean, string, number, or text. Cannot be object.');
        }
    }

    /**
     * @since 2018-03-14 17:35:37
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute($name)
    {
        return $this->_document->get($name) !== null;
    }

    public function getIsNewRecord()
    {
        return $this->_document->getIsNew();
    }
}
