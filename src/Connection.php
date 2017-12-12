<?php

namespace haqqi\arangodb;

use ArangoDBClient\ConnectionOptions;
use ArangoDBClient\UpdatePolicy;
use yii\base\Component;

class Connection extends Component
{
    private $connection = null;

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

            $this->connection = new \ArangoDBClient\Connection($this->connectionOptions);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), (int) $e->getCode(), $e);
        } finally {
            \Yii::endProfile($token, 'haqqi\arangodb\Connection::open');
        }
    }
}
