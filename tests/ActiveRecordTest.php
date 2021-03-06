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
        ActiveRecord::$db = $this->getConnection();

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
        $this->assertEquals($this->_collectionName, Post::collectionNamePrefixed());
        $this->assertEquals('user_profile', UserProfile::collectionNamePrefixed());

        $this->getConnection()->collectionPrefix = 'hq_';
        $this->assertEquals('hq_' . $this->_collectionName, Post::collectionNamePrefixed());
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
        $this->assertTrue($post->isNewRecord);
    }

    public function testGetPrimaryKey()
    {
        $post = new Post();
        $this->assertNull($post->getPrimaryKey());
    }

    public function testSetGetHasAttribute()
    {
        $post = new Post();
        $this->assertFalse($post->hasAttribute('title'));
        $post->setAttribute('title', 'This is title');
        $this->assertTrue($post->hasAttribute('title'));
        $this->assertEquals('This is title', $post->getAttribute('title'));
    }

    public function testAddObjectAsAttributeAndProperty()
    {
        $post   = new Post();
        $object = new \stdClass();

        $this->expectException(InvalidArgumentException::class);
        // set object property
        $post->objectProperty = $object;
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

    public function testDirtyAttributes()
    {
        $post          = new Post();
        $post->title   = 'Awesome';
        $post->content = 'Just Content';

        $this->assertEquals([
            'title'   => 'Awesome',
            'content' => 'Just Content'
        ], $post->getDirtyAttributes());
        $this->assertEquals([
            'title' => 'Awesome'
        ], $post->getDirtyAttributes(['title']));
    }

    public function testInsert()
    {
        $post = new Post();

        // no primary key at beginning
        $this->assertNull($post->getPrimaryKey());

        $post->innerProperty = 'Just inner property';
        $post->title         = 'Just a title';
        $post->insert();

        // primary key as string
        $this->assertTrue(\is_string($post->getPrimaryKey()));

        // test dirty attributes again
        $this->assertEmpty($post->getDirtyAttributes());
        $post->title = 'Awesome';
        $this->assertEquals([
            'title' => 'Awesome'
        ], $post->getDirtyAttributes());
    }
}
