<?php

namespace haqqi\arangodb;

use yii\base\BaseObject;
use yii\base\InvalidArgumentException;

/**
 * Class QueryBuilder
 * @package haqqi\arangodb
 *
 * @property Query $query
 */
class QueryBuilder extends BaseObject
{
    const KEYWORDS = [
        'aggregate',
        'all',
        'and',
        'any',
        'asc',
        'collect',
        'desc',
        'distinct',
        'false',
        'filter',
        'for',
        'graph',
        'in',
        'inbound',
        'insert',
        'into',
        'let',
        'limit',
        'none',
        'not',
        'null',
        'or',
        'outbound',
        'remove',
        'replace',
        'return',
        'shortest_path',
        'sort',
        'true',
        'update',
        'upsert',
        'with'
    ];

    /** @var Query */
    private $_query;

    public $separator = ' ';

    /**
     * QueryBuilder constructor.
     *
     * @param Query $query
     * @param array $config
     */
    public function __construct($query, $config = [])
    {
        $this->_query = $query;

        parent::__construct($config);
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->_query;
    }

    /**
     * @param Query $query
     */
    public function setQuery(Query $query)
    {
        $this->_query = $query;
    }

    /**
     * @since 2017-12-13 12:09:01
     *
     * @param array $params
     *
     * @return array
     */
    public function build($params = [])
    {
//        $params = empty($params) ? $query->params : array_merge($params, $query->params);

        $clauses = [
            $this->buildFrom(),
            $this->buildWhere(),
            $this->buildOrderBy(),
            $this->buildLimit(),
            $this->buildSelect()
        ];

        $clauses = \array_filter($clauses);

        $aql    = \implode($this->separator, $clauses);
        $params = $this->_whereParams;

        return [$aql, $params];
    }

    protected function buildFrom()
    {
        $collectionName = \trim($this->_query->getFrom());
        $asName         = \trim($this->_query->getAs());

        $collectionName = $this->quoteName($collectionName);
        $asName         = $this->quoteName($asName);

        return $collectionName ? "FOR $asName IN $collectionName" : '';
    }

    protected function buildWhere()
    {
        // reset just in case
        $condition          = "";
        $this->_whereParams = [];
        $where              = $this->_query->getWhere();


        if (!empty($where)) {
            if (is_array($where)) {
                $condition = $this->createWhereFromArray($where);
            } else {
                if (is_string($where)) {
                    $condition = $where;
                } else {
                    throw new InvalidArgumentException("Where arguments only support string and array");
                }
            }
        }

        return $condition === "" ? "" : "FILTER " . $condition;
    }

    protected function createWhereFromArray($condition)
    {
        // array [ operator, field, value ] 
        if (isset($condition[0])) {
            $operator = strtoupper(array_shift($condition));
            if (in_array($operator, ['AND', 'OR'])) {
                $pieces = [];
                foreach ($condition as $piece) {
                    $pieces[] = $this->createWhereFromArray($piece);
                }

                return "( " . implode(" {$operator} ", $pieces) . " )";
            } else {
                $field = $this->normalizeColumnName($condition[0]);
                $value = $this->createParamValue($condition[1]);
                return $field . " " . $operator . " " . $value;
            }
        }

        // array [ field => value ]
        $rawField = array_keys($condition);
        $field    = $this->normalizeColumnName($rawField[0]);
        $value    = $this->createParamValue($condition[$rawField[0]]);
        return $field . " == " . $value;
    }

    private $_whereParams = [];

    protected function createParamValue($value)
    {
        // random number to prevent clash with custom param
        $random                         = rand(10000, 99999);
        $number                         = count($this->_whereParams);
        $paramName                      = "where{$random}{$number}";
        $this->_whereParams[$paramName] = $value;

        return "@" . $paramName;
    }

    /**
     * @since 2017-12-12 19:31:52
     *
     * @param $collectionName
     * @param $columns
     *
     * @return string
     */
    protected function buildSelect()
    {
        $columns = $this->_query->getSelect();

        if ($columns === null || empty($columns)) {
            return 'RETURN ' . $this->quoteName($this->_query->getAs());
        }

        if (!is_array($columns)) {
            return 'RETURN ' . $columns;
        }

        $returnDefinition = [];

        foreach ($columns as $column) {
            $returnDefinition[$column] = "$column: " . $this->normalizeColumnName($column);
        }

        return "RETURN { " . \implode(', ', $returnDefinition) . " }";
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
    protected function buildLimit()
    {
        $aql    = '';
        $limit  = $this->_query->getLimit();
        $offset = $this->_query->getOffset();

        if ($this->hasLimit($limit)) {
            $aql = 'LIMIT ' . ($this->hasOffset($offset) ? $offset : '0') . ', ' . $limit;
        }

        return $aql;
    }

    protected function buildOrderBy()
    {
        $columns = $this->_query->getOrderBy();

        if (empty($columns)) {
            return '';
        }
        $orders = [];
        foreach ($columns as $name => $direction) {
            $orders[] = $this->normalizeColumnName($name) . ($direction === SORT_DESC ? ' DESC' : '');
        }

        return 'SORT ' . implode(', ', $orders);
    }

    protected function quoteName($name)
    {
        // if it is function, or already escaped, no need to quote it
        if (\strpos($name, '(') !== false) {
            return $name;
        }
        // if it is already quoted, no need to escape
        if (\strpos($name, '`') !== false) {
            return $name;
        }

        // if it is not reserved keywords, no need to escape
        if(!\in_array($name, static::KEYWORDS)) {
            return $name;
        }

        return \sprintf('`%s`', $name);
    }

    protected function quoteValue($value)
    {
        if (is_null($value)) {
            return "null";
        }

        if (!is_string($value)) {
            return $value;
        }

        $value = addslashes($value);
        return \sprintf('"%s"', $value);
    }

    protected function normalizeColumnName($name)
    {
        // if it is function, no need to normalize it
        if (\strpos($name, '(') !== false) {
            return $name;
        }
        // if it is already has collection name, no need to normalize it
        if (($pos = \strpos($name, '.')) !== false) {
            $collection = substr($name, 0, $pos);
            $collection = $this->quoteName($collection) . '.';
            $name       = \substr($name, $pos + 1);
        } else {
            $collection = $this->quoteName($this->_query->getAs()) . '.';
        }

        return $collection . $this->quoteName($name);
    }
}
