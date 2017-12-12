<?php

namespace haqqi\arangodb;

use ArangoDBClient\CollectionHandler;
use ArangoDBClient\ConnectionOptions;
use ArangoDBClient\DocumentHandler;
use ArangoDBClient\EdgeHandler;
use ArangoDBClient\Export;
use ArangoDBClient\Statement;
use ArangoDBClient\UpdatePolicy;
use yii\base\Component;

class Connection extends Component
{
    public static $componentName = 'arangodb';

    /** @var \ArangoDBClient\Connection */
    private $_connection = null;

    public $connectionOptions = [
        // server endpoint to connect to
        ConnectionOptions::OPTION_ENDPOINT      => 'tcp://127.0.0.1:8529',
        // database
        ConnectionOptions::OPTION_DATABASE      => '',
        // authorization type to use (currently supported: 'Basic')
        ConnectionOptions::OPTION_AUTH_TYPE     => 'Basic',
        // user for basic authorization
        ConnectionOptions::OPTION_AUTH_USER     => 'root',
        // password for basic authorization
        ConnectionOptions::OPTION_AUTH_PASSWD   => '',
        // connection persistence on server. can use either 'Close'
        // (one-time connections) or 'Keep-Alive' (re-used connections)
        ConnectionOptions::OPTION_CONNECTION    => 'Close',
        // connect timeout in seconds
        ConnectionOptions::OPTION_TIMEOUT       => 3,
        // whether or not to reconnect when a keep-alive connection has timed out on server
        ConnectionOptions::OPTION_RECONNECT     => true,
        // optionally create new collections when inserting documents
        ConnectionOptions::OPTION_CREATE        => true,
        // optionally create new collections when inserting documents
        ConnectionOptions::OPTION_UPDATE_POLICY => UpdatePolicy::LAST,
    ];

    /** @var null|CollectionHandler $_collectionHandler */
    private $_collectionHandler = null;
    /** @var null|DocumentHandler $_documentHandler */
    private $_documentHandler = null;
    /** @var null|EdgeHandler $_documentHandler */
    private $_edgeHandler = null;
    /** @var null|QueryBuilder $_queryBuilder */
    private $_queryBuilder = null;

    /**
     * @author Haqqi <me@haqqi.net>
     * @since 2017-12-12 07:12:51
     * @throws \Exception
     */
    public function init()
    {
        parent::init();

        $token = 'Opening ArangoDB connection: ' . $this->connectionOptions[ConnectionOptions::OPTION_ENDPOINT];

        try {
            \Yii::info($token, 'haqqi\arangodb\Connection::open');
            \Yii::beginProfile($token, 'haqqi\arangodb\Connection::open');

            // prepare the variable
            $this->_connection        = new \ArangoDBClient\Connection($this->connectionOptions);
            $this->_collectionHandler = new CollectionHandler($this->_connection);
            $this->_documentHandler   = new DocumentHandler($this->_connection);
            $this->_edgeHandler       = new EdgeHandler($this->_connection);
            $this->_queryBuilder      = new QueryBuilder();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), (int) $e->getCode(), $e);
        } finally {
            \Yii::endProfile($token, 'haqqi\arangodb\Connection::open');
        }
    }

    /**
     * @author Haqqi <me@haqqi.net>
     * @since 2017-12-12 12:05 PM
     *
     * @return CollectionHandler
     */
    public function getCollectionHandler()
    {
        return $this->_collectionHandler;
    }

    /**
     * @author Haqqi <me@haqqi.net>
     * @since 2017-12-12 12:04:55
     *
     * @param $collectionId
     *
     * @return \ArangoDBClient\Collection
     * @throws \ArangoDBClient\Exception
     */
    public function getCollection($collectionId)
    {
        return $this->_collectionHandler->get($collectionId);
    }

    /**
     * @author Haqqi <me@haqqi.net>
     * @since 2017-12-12 12:05 PM
     *
     * @return DocumentHandler
     */
    public function getDocumentHandler()
    {
        return $this->_documentHandler;
    }

    /**
     * @author Haqqi <me@haqqi.net>
     * @since 2017-12-12 12:05 PM
     *
     * @return EdgeHandler
     */
    public function getEdgeHandler()
    {
        return $this->_edgeHandler;
    }

    /**
     * @author Haqqi <me@haqqi.net>
     * @since 2017-12-12 12:05:02
     *
     * @param array $options
     *
     * @return Statement
     */
    public function getStatement($options = [])
    {
        return new Statement($this->_connection, $options);
    }

    /**
     * @author Haqqi <me@haqqi.net>
     * @since 2017-12-12 12:06:07
     *
     * @param array $options
     *
     * @return Export
     */
    public function getExport($options = [])
    {
        return new Export($this->_connection, $options);
    }

    public function getQueryBuilder()
    {
        return $this->_queryBuilder;
    }
}
