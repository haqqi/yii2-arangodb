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
     * @since 2017-12-12 19:18:51
     *
     * @param $collectionName
     *
     * @return $this
     */
    public function from($collectionName)
    {
        $this->_from = $collectionName;

        return $this;
    }

    /**
     * Build from statement in AQL
     *
     * @since 2017-12-12 19:19:27
     *
     * @return string
     */
    protected function buildFrom()
    {
        $collectionName = \trim($this->_from);

        return $collectionName ? "FOR $collectionName IN $collectionName" : '';
    }

    /**
     * @since 2017-12-12 19:25:40
     *
     * @param $fields
     *
     * @return $this
     */
    public function select($fields)
    {
        $this->_select = $fields;

        return $this;
    }

    /**
     * @since 2017-12-12 19:31:52
     * @return string
     */
    protected function buildSelect()
    {
        $columns = $this->_select;

        if ($columns === null || empty($columns)) {
            return 'RETURN ' . $this->_from;
        }

        if (!is_array($columns)) {
            return 'RETURN ' . $columns;
        }

        $returnDefinition = '';

        foreach ($columns as $column) {
            $returnDefinition .= "\"$column\": $this->_from . $column,\n";
        }

        return "RETURN {\n" . trim($returnDefinition, ', ') . "\n}";
    }

    /**
     * @since 2017-12-12 19:22:28
     *
     * @param int|null $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->_limit = $limit;

        return $this;
    }

    /**
     * @since 2017-12-12 19:23:37
     *
     * @param $limit
     *
     * @return bool
     */
    protected function hasLimit($limit)
    {
        return is_string($limit) && ctype_digit($limit) || is_integer($limit) && $limit >= 0;
    }

    /**
     * @since 2017-12-12 19:22:33
     *
     * @param int|null $offset
     *
     * @return $this
     */
    public function offset($offset)
    {
        $this->_offset = $offset;

        return $this;
    }

    /**
     * @since 2017-12-12 19:23:56
     *
     * @param $offset
     *
     * @return bool
     */
    protected function hasOffset($offset)
    {
        return is_integer($offset) && $offset > 0 || is_string($offset) && ctype_digit($offset) && $offset !== '0';
    }

    /**
     * @since 2017-12-12 19:24:21
     *
     * @param $limit
     * @param $offset
     *
     * @return string
     */
    protected function buildLimit($limit, $offset)
    {
        $aql = '';
        if ($this->hasLimit($limit)) {
            $aql = 'LIMIT ' . ($this->hasOffset($offset) ? $offset : '0') . ',' . $limit;
        }

        return $aql;
    }
}
