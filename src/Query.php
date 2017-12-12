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

    public function select($fields)
    {
        $this->_select = $fields;

        return $this;
    }

    public function from($collectionId)
    {
        $this->_from = $collectionId;

        return $this;
    }

    public function limit($limit)
    {
        $this->_limit = $limit;
    }

    protected function quoteName($name)
    {
        return "`$name`";
    }


}
