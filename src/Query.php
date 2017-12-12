<?php

namespace haqqi\arangodb;

use yii\base\Component;
use yii\base\NotSupportedException;
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

    /**
     * @since 2017-12-12 20:17:54
     *
     * @param array $condition
     *
     * @return $this
     * @throws NotSupportedException
     */
    public function filterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);
        if ($condition !== []) {
            $this->where($condition);
        }

        return $this;
    }

    /**
     * @since 2017-12-12 20:21:38
     *
     * @param array $condition
     *
     * @return $this
     * @throws NotSupportedException
     */
    public function andFilterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);
        if ($condition !== []) {
            $this->andWhere($condition);
        }

        return $this;
    }

    /**
     * @since 2017-12-12 20:21:59
     *
     * @param array $condition
     *
     * @return $this
     * @throws NotSupportedException
     */
    public function orFilterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);
        if ($condition !== []) {
            $this->orWhere($condition);
        }

        return $this;
    }


    /**
     * @since 2017-12-12 20:17:09
     * @see \yii\db\QueryTrait
     *
     * @param $condition
     *
     * @return array
     * @throws NotSupportedException
     */
    protected function filterCondition($condition)
    {
        if (!is_array($condition)) {
            return $condition;
        }

        if (!isset($condition[0])) {
            // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
            foreach ($condition as $name => $value) {
                if ($this->isEmpty($value)) {
                    unset($condition[$name]);
                }
            }

            return $condition;
        }

        // operator format: operator, operand 1, operand 2, ...

        $operator = array_shift($condition);

        switch (strtoupper($operator)) {
            case 'NOT':
            case 'AND':
            case 'OR':
                foreach ($condition as $i => $operand) {
                    $subCondition = $this->filterCondition($operand);
                    if ($this->isEmpty($subCondition)) {
                        unset($condition[$i]);
                    } else {
                        $condition[$i] = $subCondition;
                    }
                }

                if (empty($condition)) {
                    return [];
                }
                break;
            case 'IN':
            case 'LIKE':
                if (array_key_exists(1, $condition) && $this->isEmpty($condition[1])) {
                    return [];
                }
                break;
            case 'BETWEEN':
            case 'NOT BETWEEN':
                if (array_key_exists(1, $condition) && array_key_exists(2, $condition)) {
                    if ($this->isEmpty($condition[1]) || $this->isEmpty($condition[2])) {
                        return [];
                    }
                }
                break;
            default:
                throw new NotSupportedException("Operator not supported: $operator");
        }

        array_unshift($condition, $operator);

        return $condition;
    }

    /**
     * Returns a value indicating whether the give value is "empty".
     *
     * The value is considered "empty", if one of the following conditions is satisfied:
     *
     * - it is `null`,
     * - an empty string (`''`),
     * - a string containing only whitespace characters,
     * - or an empty array.
     *
     * @see \yii\db\QueryTrait
     *
     * @param mixed $value
     *
     * @return bool if the value is empty
     */
    protected function isEmpty($value)
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }
}
