<?php

namespace haqqi\arangodb;

use yii\base\BaseObject;

class QueryBuilder extends BaseObject
{
    public $separator = ' ';

    /**
     * @since 2017-12-13 12:09:01
     *
     * @param Query $query
     * @param array $params
     *
     * @return string
     */
    public function build($query, $params = [])
    {
        $params = empty($params) ? $query->params : array_merge($params, $query->params);

        $clauses = [
            $this->buildFrom($query->getFrom()),
            $this->buildLimit($query->getLimit(), $query->getOffset()),
            $this->buildSelect($query->getFrom(), $query->getSelect())
        ];

        $aql = \implode($this->separator, $clauses);

        return $aql;
    }

    /**
     * @since 2017-12-12 19:39:26
     *
     * @param $collectionName
     *
     * @return string
     */
    protected function buildFrom($collectionName)
    {
        $collectionName = \trim($collectionName);

        return $collectionName ? "FOR $collectionName IN $collectionName" : '';
    }

    /**
     * @since 2017-12-12 19:31:52
     *
     * @param $collectionName
     * @param $columns
     *
     * @return string
     */
    protected function buildSelect($collectionName, $columns)
    {
        if ($columns === null || empty($columns)) {
            return 'RETURN ' . $collectionName;
        }

        if (!is_array($columns)) {
            return 'RETURN ' . $columns;
        }

        $returnDefinition = [];

        foreach ($columns as $column) {
            $returnDefinition[$column] = "$collectionName.$column"; // @todo: quote the column
        }

        return "RETURN { " . \implode(', ', $returnDefinition) . "}";
    }

    /**
     * @param $limit
     *
     * @return bool
     */
    protected function hasLimit($limit)
    {
        return is_string($limit) && ctype_digit($limit) || is_integer($limit) && $limit >= 0;
    }

    /**
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
            $aql = 'LIMIT ' . ($this->hasOffset($offset) ? $offset : '0') . ', ' . $limit;
        }

        return $aql;
    }
}
