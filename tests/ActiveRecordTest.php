<?php

namespace haqqi\tests\arangodb;

use ArangoDBClient\Collection;
use ArangoDBClient\CollectionHandler;
use haqqi\tests\arangodb\data\bridge\ActiveRecord;
use haqqi\tests\arangodb\data\bridge\Post;
use haqqi\tests\arangodb\data\bridge\UserProfile;
use yii\base\InvalidArgumentException;

class ActiveRecordTest extends TestCase
{
    /** @var CollectionHandler */
    private $_collectionHandler;

    private $_collectionName = 'post';

    protected function setUp()
    {
        $this->_collectionHandler = $this->getConnection()->collectionHandler;

        // reset collection
        if ($this->_collectionHandler->has($this->_collectionName)) {
            $this->_collectionHandler->drop($this->_collectionName);
        }

        $this->_collectionHandler->create($this->_collectionName);
    }

    public function testPrimaryKey()
    {
        $this->assertTrue(ActiveRecord::isPrimaryKey(['_id']));
        $this->assertFalse(ActiveRecord::isPrimaryKey(['_key']));
    }

    public function testCollectionName()
    {
        $this->assertEquals('`%post`', Post::collectionName());
        $this->assertEquals('`%user_profile`', UserProfile::collectionName());
    }

    public function testCollectionExistence()
    {
        $collection = $this->_collectionHandler->get($this->_collectionName);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals($this->_collectionName, $collection->getName());
    }

    public function testIsNewRecord()
    {
        $post = new Post();
        $this->assertTrue($post->getIsNewRecord());
    }

    public function testAddObjectAsAttributeAndProperty()
    {
        $post   = new Post();
        $object = new \stdClass();

        $this->expectException(InvalidArgumentException::class);
        // set object property
        $post->setObjectProperty($object);
        // set object as attribute
        $post->tryObject = $object;
    }

    public function testSetProperty()
    {
        $post                   = new Post();
        $post->innerProperty    = 'Inner property';
        $post->documentProperty = 'Document property';

        $this->assertEquals('Inner property', $post->innerProperty);
        $this->assertEquals('Document property', $post->documentProperty);
        $this->assertFalse($post->__isset('random'));
        $this->assertTrue($post->__isset('documentProperty'));
    }
}
