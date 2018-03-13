<?php

namespace haqqi\tests\arangodb;

use ArangoDBClient\CollectionHandler;
use ArangoDBClient\DocumentHandler;
use ArangoDBClient\EdgeHandler;
use ArangoDBClient\GraphHandler;
use ArangoDBClient\Statement;
use haqqi\arangodb\Connection;

class ConnectionTest extends TestCase
{
    public function testConnection()
    {
        $arangodb = $this->getConnection();

        $this->assertTrue($arangodb instanceof Connection);
        $this->assertTrue($arangodb->collectionHandler instanceof CollectionHandler);
        $this->assertTrue($arangodb->documentHandler instanceof DocumentHandler);
        $this->assertTrue($arangodb->edgeHandler instanceof EdgeHandler);
        $this->assertTrue($arangodb->graphHandler instanceof GraphHandler);
        $this->assertTrue($arangodb->statement instanceof Statement);
    }
}
