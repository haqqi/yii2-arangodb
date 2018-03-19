<?php

namespace haqqi\arangodb;

use ArangoDBClient\Cursor;
use ArangoDBClient\Statement;
use yii\base\Component;
use yii\base\NotSupportedException;
use yii\db\QueryInterface;
use yii\helpers\ArrayHelper;

/**
 * Class Query
 * @package haqqi\arangodb
 *
 * @property QueryBuilder $builder
 */
class Query extends Component implements QueryInterface
{
    /** @var string Collection id */
    private $_from;
    /** @var string Main document alias in loop */
    private $_as;
    /** @var array Attribute name */
    private $_select;
    /** @var int Limit of the record */
    private $_limit;
    /** @var int Offset of the record */
    private $_offset;
    /** @var array Where condition */
    private $_where;
    /** @var array Order by rule */
    private $_orderBy;
    /** @var string Index by column */
    private $_indexBy;

    /** @var QueryBuilder */
    private $_builder;

    private $_params = [];

    private $_options = [];

    ////////////////////////////
    //// Getter Area ///////////
    ////////////////////////////

    /**
     * @since 2017-12-14 10:16:34
     * @return string
     */
    public function getFrom(): string
    {
        return $this->_from;
    }

    /**
     * @since 2017-12-14 11:45:22
     * @return string
     */
    public function getAs(): string
    {
        return $this->_as;
    }

    /**
     * @return array
     */
    public function getSelect()
    {
        return $this->_select;
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * @return int|null
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * @return array|null|string
     */
    public function getWhere()
    {
        return $this->_where;
    }

    /**
     * @return array|null
     */
    public function getOrderBy()
    {
        return $this->_orderBy;
    }

    /**
     * @since 2017-12-16 10:12:08
     * @return QueryBuilder
     */
    public function getBuilder()
    {
        if ($this->_builder === null) {
            $this->_builder = new QueryBuilder($this);
        }

        return $this->_builder;
    }

    /**
     * params to be added in bindvar
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->_params;
    }

    /**
     * @return string|null
     */
    public function getIndexBy()
    {
        return $this->_indexBy;
    }

    /**
     * Options to be added in the arango statement
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    ////////////////////////////
    /// End of Getter Area /////
    ////////////////////////////

    ////////////////////////////
    // Active Query Area ///////
    ////////////////////////////

    /**
     * Set collection name. Must be called only once.
     *
     * @param string $collectionName
     * @param string $as Variable name to be run in "AQL for loop". If it is not set, it will use collection name.
     *
     * @return $this
     */
    public function from($collectionName, $as = null): Query
    {
        $this->_from = $collectionName;
        $this->_as   = (empty($as)) ? $collectionName : $as;

        return $this;
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
     * @since 2017-12-12 19:58:12
     *
     * @param array|string $condition
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

    /**
     * @since 2017-12-12 20:36:40
     *
     * @param array|string $columns
     *
     * @return $this
     */
    public function orderBy($columns)
    {
        $this->_orderBy = $this->normalizeOrderBy($columns);

        return $this;
    }

    /**
     * @since 2017-12-12 20:36:43
     *
     * @param array|string $columns
     *
     * @return $this
     */
    public function addOrderBy($columns)
    {
        $columns = $this->normalizeOrderBy($columns);
        if ($this->_orderBy === null) {
            $this->_orderBy = $columns;
        } else {
            $this->_orderBy = array_merge($this->_orderBy, $columns);
        }

        return $this;
    }

    /**
     * @since 2017-12-12 20:36:49
     *
     * @param $columns
     *
     * @return array|array[]|false|string[]
     */
    protected function normalizeOrderBy($columns)
    {
        if (is_array($columns)) {
            return $columns;
        } else {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
            $result  = [];
            foreach ($columns as $column) {
                if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                    $result[$matches[1]] = strcasecmp($matches[2], 'desc') ? SORT_ASC : SORT_DESC;
                } else {
                    $result[$column] = SORT_ASC;
                }
            }

            return $result;
        }
    }

    /**
     * @since 2017-12-16 00:23:58
     *
     * @param callable|string $column
     *
     * @return $this|void
     */
    public function indexBy($column)
    {
        $this->_indexBy = $column;
    }

    /**
     * @since 2017-12-16 12:12:57
     *
     * @param $params
     *
     * @return $this
     */
    public function params($params): Query
    {
        $this->_params = $params;

        return $this;
    }

    /**
     * @since 2017-12-16 12:14:24
     *
     * @param $params
     *
     * @return Query
     */
    public function addParams($params): Query
    {
        if (!empty($params)) {
            if (empty($this->_params)) {
                $this->_params = $params;
            } else {
                foreach ($params as $name => $value) {
                    if (is_integer($name)) {
                        $this->_params[] = $value;
                    } else {
                        $this->_params[$name] = $value;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @since 2017-12-16 17:01:07
     *
     * @param $options
     *
     * @return $this
     */
    public function options($options): Query
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * @since 2017-12-16 17:01:11
     *
     * @param $options
     *
     * @return $this
     */
    public function addOptions($options): Query
    {
        if (!empty($options)) {
            if (empty($this->_options)) {
                $this->_options = $options;
            } else {
                foreach ($options as $name => $value) {
                    if (is_integer($name)) {
                        $this->_options[] = $value;
                    } else {
                        $this->_options[$name] = $value;
                    }
                }
            }
        }
        return $this;
    }

    /////////////////////////////////////////
    // End of Active Query Area /////////////
    /////////////////////////////////////////

    /////////////////////////////////////////
    // Start of query method ////////////////
    /////////////////////////////////////////

    public function count($q = '*', $db = null)
    {
        // save for temporarily variable
        $select  = $this->_select;
        $orderBy = $this->_orderBy;
        $limit   = $this->_limit;
        $offset  = $this->_offset;

        // set it to null
        $this->_select  = $q; // no need to select anything
        $this->_orderBy = null;
        $this->_limit   = null;
        $this->_offset  = null;


        $this->_select  = $select;
        $this->_orderBy = $orderBy;
        $this->_limit   = $limit;
        $this->_offset  = $offset;
    }

    public function exists($db = null)
    {
        throw new NotSupportedException('Exists is still not supported.');
    }

    public function emulateExecution($value = true)
    {
        throw new NotSupportedException('emulateExecution is still not supported.');
    }
    
    /**
     * Converts the raw query results into the format as specified by this query.
     * This method is internally used to convert the data fetched from database
     * into the format as required by this query.
     * 
     * @param array $rows the raw query result from database
     * @return array the converted query result
     */
    public function populate($rows)
    {
        if ($this->indexBy === null) {
            return $rows;
        }
        $result = [];
        foreach ($rows as $row) {
            $result[ArrayHelper::getValue($row, $this->indexBy)] = $row;
        }
        
        return $result;
    }
    
    /**
     * Executes the query and returns all results as an array.
     *
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return array the query results. If the query results in nothing, an empty array
     * will be returned.
     * @throws \ArangoDBClient\Exception
     */
    public function all($db = null)
    {
        $command = $this->createCommand($db);
        $rows = $command->getAll();
        return $this->populate($rows);
    }
    
    /**
     * Executes the query and returns a single row of result.
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return array|bool the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     * @throws \ArangoDBClient\Exception
     */
    public function one($db = null)
    {
        $data = $this->all($db);
        if (is_array($data)) {
            return ArrayHelper::getValue($data, 0);
        }
        
        return null;
    }
    
    public function createCommand($db = null): Cursor
    {
        if (is_null($db)) {
            $db = \Yii::$app->arangodb;
        }
        
        $queryBuilder = new QueryBuilder($this);
        list($aql, $params) = $queryBuilder->build();
        $query = $db->getStatement([
            Statement::ENTRY_QUERY => $aql,
            Statement::ENTRY_BINDVARS => $params
        ]);
        
        return $query->execute();
    }

    //////////////////////////////////////////
    /// This is it ///////////////////////////
    //////////////////////////////////////////
}
