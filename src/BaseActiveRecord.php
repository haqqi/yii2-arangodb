<?php

namespace haqqi\arangodb;

use ArangoDBClient\ClientException;
use ArangoDBClient\Document;
use ArangoDBClient\Exception;
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
    /**
     * @event Event an event that is triggered when the record is initialized via [[init()]].
     */
    const EVENT_INIT = 'init';
    /**
     * @event Event an event that is triggered after the record is created and populated with query result.
     */
    const EVENT_AFTER_FIND = 'afterFind';
    /**
     * @event ModelEvent an event that is triggered before inserting a record.
     * You may set [[ModelEvent::isValid]] to be `false` to stop the insertion.
     */
    const EVENT_BEFORE_INSERT = 'beforeInsert';
    /**
     * @event AfterSaveEvent an event that is triggered after a record is inserted.
     */
    const EVENT_AFTER_INSERT = 'afterInsert';
    /**
     * @event ModelEvent an event that is triggered before updating a record.
     * You may set [[ModelEvent::isValid]] to be `false` to stop the update.
     */
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';
    /**
     * @event AfterSaveEvent an event that is triggered after a record is updated.
     */
    const EVENT_AFTER_UPDATE = 'afterUpdate';
    /**
     * @event ModelEvent an event that is triggered before deleting a record.
     * You may set [[ModelEvent::isValid]] to be `false` to stop the deletion.
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';
    /**
     * @event Event an event that is triggered after a record is deleted.
     */
    const EVENT_AFTER_DELETE = 'afterDelete';
    /**
     * @event Event an event that is triggered after a record is refreshed.
     * @since 2.0.8
     */
    const EVENT_AFTER_REFRESH = 'afterRefresh';

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
        // @todo: create format for prefix
        return '%' . Inflector::camel2id(StringHelper::basename(get_called_class()), '_');
    }

    public static function collectionNamePrefixed()
    {
        return static::getDb()->replaceCollectionPrefix(static::collectionName());
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

    /**
     * Returns a value indicating whether the current record is new.
     * @return bool whether the record is new and should be inserted when calling [[save()]].
     */
    public function getIsNewRecord()
    {
        return $this->_document->getIsNew();
    }

    /**
     * @since 2018-03-16 13:14:46
     *
     * @param bool $runValidation
     * @param null $attributes
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            \Yii::info(\get_called_class() . ' not inserted due to validation error.', __METHOD__);
            return false;
        }

        // note: because ArangoDB transaction is using fully js, it is not supported
        // in Active Record. https://docs.arangodb.com/3.3/Manual/Transactions/

        // @todo: prepare event before save

        try {
            $documentHandler = static::getDb()->documentHandler;
            $documentHandler->save(static::collectionNamePrefixed(), $this->_document);
        } catch (Exception $e) {
            throw new \yii\base\Exception($e->getMessage());
        }

        // @todo: prepare event after save
    }
}
