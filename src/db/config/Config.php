<?php

namespace RecordTracker\db\config;

/**
 * Class Config
 * @package RecordTracker\db\config
 */
final class Config
{
    private $host;
    private $user;
    private $password;
    private $db;
    private $port;
    private $schema;

    public function __construct($config = [])
    {
        if (!$config) {
            throw new \Exception('The configuration is empty. Please provide the necessary configuration parameters (host, user, password, db, port).');
        }
        $attrs = [
            'host',
            'user',
            'password',
            'db',
            'port',
            'schema',
        ];
        foreach ($attrs as $attr) {
            if (!isset($config[$attr])) {
                throw new \Exception('Required parameter "' . $attr . '" missing!');
            }
            $this->$attr = $config[$attr];
        }
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return mixed
     */
    public function getSchema()
    {
        return ($this->schema ? $this->schema . '.' : '');
    }
}
