<?php

namespace haqqi\arangodb;

use yii\base\Component;
use yii\db\QueryInterface;

class Query extends Component implements QueryInterface
{
    /** @var array Attribute name */
    private $_select = [];
    /** @var string Collection id */
    private $_from;
    /** @var int Limit of the record */
    private $_limit;
    /** @var int Offset of the record */
    private $_offset;
    /** @var array Where condition */
    private $_where;

    /**
     * Set collection name. Must be called only once.
     *
     * @param $collectionName
     *
     * @return $this
     */
    public function from($collectionName): Query
    {
        $this->_from = $collectionName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->_from;
    }

    /**
     * @param $fields
     *
     * @return $this
     */
    public function select($fields): Query
    {
        $this->_select = $fields;

        return $this;
    }

    /**
     * @return array
     */
    public function getSelect(): array
    {
        return $this->_select;
    }

    /**
     * @param int|null $limit
     *
     * @return $this
     */
    public function limit($limit): Query
    {
        $this->_limit = $limit;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->_limit;
    }

    /**
     * @since 2017-12-12 19:22:33
     *
     * @param int|null $offset
     *
     * @return $this
     */
    public function offset($offset): Query
    {
        $this->_offset = $offset;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->_offset;
    }

    /**
     * @since 2017-12-12 19:58:12
     *
     * @param array $condition
     *
     * @return Query
     */
    public function where($condition): Query
    {
        $this->_where = $condition;

        return $this;
    }

    /**
     * @since 2017-12-12 19:57:49
     *
     * @param array $condition
     *
     * @return Query
     */
    public function andWhere($condition): Query
    {
        if ($this->_where === null) {
            $this->_where = $condition;
        } elseif (is_array($this->_where) && isset($this->_where[0]) && strcasecmp($this->_where[0], 'and') === 0) {
            $this->_where[] = $condition;
        } else {
            $this->_where = ['and', $this->_where, $condition];
        }
        return $this;
    }

    /**
     * @since 2017-12-12 19:59:26
     *
     * @param array $condition
     *
     * @return Query
     */
    public function orWhere($condition): Query
    {
        if ($this->_where === null) {
            $this->_where = $condition;
        } else {
            $this->_where = ['or', $this->_where, $condition];
        }
        
        return $this;
    }

    /**
     * @return array|null|string
     */
    public function getWhere()
    {
        return $this->_where;
    }
}
