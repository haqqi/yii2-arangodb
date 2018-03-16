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

        $this->assertTrue($arangodb->isActive);
        $this->assertInstanceOf(Connection::class, $arangodb);
        $this->assertInstanceOf(CollectionHandler::class, $arangodb->collectionHandler);
        $this->assertInstanceOf(DocumentHandler::class, $arangodb->documentHandler);
        $this->assertInstanceOf(EdgeHandler::class, $arangodb->edgeHandler);
        $this->assertInstanceOf(GraphHandler::class, $arangodb->graphHandler);
        $this->assertInstanceOf(Statement::class, $arangodb->statement);
    }
}
