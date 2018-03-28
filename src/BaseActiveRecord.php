<?php

namespace haqqi\arangodb;

use ArangoDBClient\ClientException;
use ArangoDBClient\Document;
use ArangoDBClient\Exception;
use ArangoDBClient\ValueValidator;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\base\UnknownMethodException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQueryInterface;
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

    /**
     * @var string Document class to be used
     */
    public $documentClass = Document::class;
    /**
     * @var Document to hold attribute of the active record. It also serves as old attributes
     */
    private $_document;
    /**
     * @var array attribute values indexed by attribute names
     */
    private $_attributes = [];
    /**
     * @var array related models indexed by the relation names
     */
    private $_related = [];
    /**
     * @var array relation names indexed by their link attributes
     */
    private $_relationsDependencies = [];

    /**
     * @done
     * @return Connection|null
     */
    public static function getDb()
    {
        return \Yii::$app->arangodb;
    }

    /**
     * @done
     * @return string
     */
    public static function collectionName()
    {
        return '%' . Inflector::camel2id(StringHelper::basename(get_called_class()), '_');
    }

    /**
     * @done
     * @return mixed
     */
    public static function collectionNamePrefixed()
    {
        return static::getDb()->replaceCollectionPrefix(static::collectionName());
    }

    /**
     * @done
     * Initializes the object.
     * This method is called at the end of the constructor.
     * The default implementation will trigger an [[EVENT_INIT]] event.
     */
    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);
    }

    /**
     * @done
     * Returns a value indicating whether the current record is new.
     * @return bool whether the record is new and should be inserted when calling [[save()]].
     */
    public function getIsNewRecord()
    {
        return $this->_document === null;
    }

    /**
     * Only _id is the primary key
     *
     * @done
     * @return string[]
     */
    public static function primaryKey()
    {
        return ['_id'];
    }

    /**
     * @done
     * Only _id is the primary key
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

    /**
     * @done
     * @since 2018-03-19 09:25:35
     *
     * @param bool $asArray
     *
     * @return mixed|null
     */
    public function getPrimaryKey($asArray = false)
    {
        return $this->getIsNewRecord() ? null : $this->_document->getId();
    }

    public function getOldPrimaryKey($asArray = false)
    {
        return $this->getPrimaryKey($asArray);
    }

    public function __get($name)
    {
        try {
            $value = parent::__get($name);
            if ($value instanceof ActiveQueryInterface) {
                $this->setRelationDependencies($name, $value);
                return $this->_related[$name] = $value->findFor($name, $this);
            }
            return $value;
        } catch (UnknownPropertyException $e) {
            return $this->getAttribute($name);
        }
    }

    /**
     * @since 2018-03-19 09:51:17
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
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
     * @done
     * Returns the named attribute value.
     * If this record is the result of a query and the attribute is not loaded,
     * `null` will be returned.
     *
     * @param string $name the attribute name
     *
     * @return mixed the attribute value. `null` if the attribute is not set or does not exist.
     * @see hasAttribute()
     */
    public function getAttribute($name)
    {
        return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
    }

    /**
     * @done
     * @since 2018-03-14 17:35:32
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setAttribute($name, $value)
    {
        try {
            ValueValidator::validate($value);

            if (!empty($this->_relationsDependencies[$name])) {
                $this->resetDependentRelations($name);
            }

            $this->_attributes[$name] = $value;
        } catch (ClientException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * @done
     * @since 2018-03-14 17:35:37
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute($name)
    {
        return isset($this->_attributes[$name]);
    }

    /**
     * @since 2018-03-16 13:14:46
     *
     * @param bool $runValidation
     * @param null $attributes
     *
     * @return bool
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

        $dirtyAttributes = $this->getDirtyAttributes($attributes);

        try {
            $documentClass = $this->documentClass;
            /** @var Document $document */
            $document = new $documentClass();

            // set the attribute
            foreach ($dirtyAttributes as $name => $value) {
                $document->set($name, $value);
            }

            // finally, save it!!
            $documentHandler = static::getDb()->documentHandler;
            $documentHandler->save(static::collectionNamePrefixed(), $document);

            // update attributes
            $this->_attributes = $document->getAll();
            // put this as old attributes
            $this->_document = $document;

            // @todo: prepare event after save
        } catch (Exception $e) {
            \Yii::info(\get_called_class() . ' not inserted due to database server error.', __METHOD__);
            return false;
        }
    }

    /**
     * Returns the attribute values that have been modified since they are loaded or saved most recently.
     *
     * The comparison of new and old values is made for identical values using `===`.
     *
     * @param string[]|null $names the names of the attributes whose values may be returned if they are
     * changed recently. If null, all attributes will be used.
     *
     * @return array the changed attribute values (name-value pairs)
     */
    public function getDirtyAttributes($names = null)
    {
        if ($names !== null) {
            $names = \array_flip($names);
        }
        // setup the attributes
        $attributes = [];

        if ($this->_document === null) {
            foreach ($this->_attributes as $name => $value) {
                if ($names === null || isset($names[$name])) {
                    $attributes[$name] = $value;
                }
            }
        } else {
            foreach ($this->_attributes as $name => $value) {
                if (($names === null || isset($names[$name])) && $value !== $this->_document->get($name)) {
                    $attributes[$name] = $value;
                }
            }
        }

        return $attributes;
    }

    /**
     * @done
     * Returns the relation object with the specified name.
     * A relation is defined by a getter method which returns an [[ActiveQueryInterface]] object.
     * It can be declared in either the Active Record class itself or one of its behaviors.
     *
     * @param string $name the relation name, e.g. `orders` for a relation defined via `getOrders()` method
     *     (case-sensitive).
     * @param bool   $throwException whether to throw exception if the relation does not exist.
     *
     * @return ActiveQueryInterface|ActiveQuery the relational query object. If the relation does not exist
     * and `$throwException` is `false`, `null` will be returned.
     * @throws InvalidArgumentException if the named relation does not exist.
     * @throws \ReflectionException
     */
    public function getRelation($name, $throwException = true)
    {
        $getter = 'get' . $name;
        try {
            // the relation could be defined in a behavior
            $relation = $this->$getter();
        } catch (UnknownMethodException $e) {
            if ($throwException) {
                throw new InvalidArgumentException(get_class($this) . ' has no relation named "' . $name . '".', 0, $e);
            }

            return null;
        }
        if (!$relation instanceof ActiveQueryInterface) {
            if ($throwException) {
                throw new InvalidArgumentException(get_class($this) . ' has no relation named "' . $name . '".');
            }

            return null;
        }

        if (method_exists($this, $getter)) {
            // relation name is case sensitive, trying to validate it when the relation is defined within this class
            $method   = new \ReflectionMethod($this, $getter);
            $realName = lcfirst(substr($method->getName(), 3));
            if ($realName !== $name) {
                if ($throwException) {
                    throw new InvalidArgumentException('Relation names are case sensitive. ' . get_class($this) . " has a relation named \"$realName\" instead of \"$name\".");
                }

                return null;
            }
        }

        return $relation;
    }

    /**
     * Populates the named relation with the related records.
     * Note that this method does not check if the relation exists or not.
     *
     * @param string                           $name the relation name, e.g. `orders` for a relation defined via
     *     `getOrders()` method (case-sensitive).
     * @param ActiveRecordInterface|array|null $records the related records to be populated into the relation.
     *
     * @see getRelation()
     */
    public function populateRelation($name, $records)
    {
        $this->_related[$name] = $records;
    }

    /**
     * Check whether the named relation has been populated with records.
     *
     * @param string $name the relation name, e.g. `orders` for a relation defined via `getOrders()` method
     *     (case-sensitive).
     *
     * @return bool whether relation has been populated with records.
     * @see getRelation()
     */
    public function isRelationPopulated($name)
    {
        return array_key_exists($name, $this->_related);
    }

    /**
     * Returns all populated related records.
     * @return array an array of related records indexed by relation names.
     * @see getRelation()
     */
    public function getRelatedRecords()
    {
        return $this->_related;
    }

    /**
     * Returns a value indicating whether the given active record is the same as the current one.
     * The comparison is made by comparing the table names and the primary key values of the two active records.
     * If one of the records [[isNewRecord|is new]] they are also considered not equal.
     *
     * @param ActiveRecordInterface $record record to compare to
     *
     * @return bool whether the two active records refer to the same row in the same database table.
     */
    public function equals($record)
    {
        if ($this->getIsNewRecord() || $record->getIsNewRecord()) {
            return false;
        }

        return get_class($this) === get_class($record) && $this->getPrimaryKey() === $record->getPrimaryKey();
    }

    /**
     * Resets dependent related models checking if their links contain specific attribute.
     *
     * @param string $attribute The changed attribute name.
     */
    private function resetDependentRelations($attribute)
    {
        foreach ($this->_relationsDependencies[$attribute] as $relation) {
            unset($this->_related[$relation]);
        }
        unset($this->_relationsDependencies[$attribute]);
    }

    /**
     * Sets relation dependencies for a property
     *
     * @param string               $name property name
     * @param ActiveQueryInterface $relation relation instance
     */
    private function setRelationDependencies($name, $relation)
    {
        if (empty($relation->via) && $relation->link) {
            foreach ($relation->link as $attribute) {
                $this->_relationsDependencies[$attribute][$name] = $name;
            }
        } elseif ($relation->via instanceof ActiveQueryInterface) {
            $this->setRelationDependencies($name, $relation->via);
        } elseif (is_array($relation->via)) {
            list(, $viaQuery) = $relation->via;
            $this->setRelationDependencies($name, $viaQuery);
        }
    }
}
