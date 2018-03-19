<?php

namespace haqqi\tests\arangodb\data\bridge;

/**
 * Class Post
 * @package haqqi\tests\arangodb\data\bridge
 *
 * @property string    $innerProperty
 * @property \stdClass $objectProperty
 */
class Post extends ActiveRecord
{
    /** @var string */
    private $_innerProperty;

    private $_objectProperty;

    /**
     * @return string
     */
    public function getInnerProperty()
    {
        return $this->_innerProperty;
    }

    /**
     * @param string $innerProperty
     */
    public function setInnerProperty($innerProperty)
    {
        $this->_innerProperty = $innerProperty;
    }

    /**
     * @param mixed $objectProperty
     */
    public function setObjectProperty($objectProperty)
    {
        $this->_objectProperty = $objectProperty;
    }
}