<?php
/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace RecordTracker\db\config;

/**
 * Class Config. A simple class that holds the parameters for initializing a database connection.
 *
 * @package RecordTracker\db\config
 * @author Vasilis Lourdas dev@lourdas.eu
 * @version 0.2.0
 */
final class Config
{
    /** @var string The database connection type, one of 'mysql', 'pgsql', etc. */
    private $dbType;
    /** @var string $host The database host name */
    private $host;
    /** @var string $user The username used for the database connection */
    private $user;
    /** @var string $password The password of the user for the database connection */
    private $password;
    /** @var string $db The name of the database to connect */
    private $db;
    /** @var integer $port The port name where the database server listens */
    private $port;
    /** @var string $schema The schema name that holds the record tracker tables. This is usually used
     *  for PostgreSQL databases, for MySQL/MariaDB databases this can me empty.
     */
    private $schema;

    /**
     * Config constructor. Checks if the required attributes are set in the ```$config``` parameter.
     *
     * @param array $config The parameters for connecting to a database. This is a key-value array.
     * The required attributes are:
     * * dbType: The database system, one of 'mysql', 'pgsql', etc.
     * * host: The database host name
     * * user: The username used for the database connection
     * * password: The password of the user for the database connection
     * * db: The name of the database to connect
     * Two optional parameters are:
     * * port: The port name where the database server listens
     * * schema: The schema name that holds the record tracker tables. This is usually used in databases where schemas
     * are used, like PostgreSQL, Oracle, etc.
     *
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        if (!$config) {
            throw new \Exception('The configuration for the Config instance is empty. Please provide '
                . 'the required configuration parameters (dbType, host, user, password, db).');
        }
        $attrs = [
            'dbType',
            'host',
            'user',
            'password',
            'db',
        ];
        foreach ($attrs as $attr) {
            if (!isset($config[$attr])) {
                throw new \Exception('Required parameter "' . $attr . '" missing for Config instance!');
            }
            $this->$attr = $config[$attr];
        }
        // set the schema if it is set in $config
        $this->schema = (isset($config['schema']) ? $config['schema'] : '');
        // set the port if it is set in $config
        $this->port = (isset($config['port']) ? $config['port'] : null);
    }

    /**
     * Returns the database host name.
     *
     * @return string The database host name
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Returns the username for the database connection.
     *
     * @return string The username for the database connection
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns the password of the user for the database connection.
     *
     * @return string Return the password of the user for the database connection
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the database name where the record tracking tables reside.
     *
     * @return string The database name
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Returns the server port where the database server listens.
     *
     * @return integer The server port where the database server listens
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Returns the schema name where the record tracking tables reside.
     *
     * @return string The schema name where the record tracking tables reside
     */
    public function getSchema()
    {
        return ($this->schema ? $this->schema . '.' : '');
    }

    /**
     * Returns the database type. Currently supported database systems are MySQL/MariaDB ('mysql')
     * and PostgreSQL ('pgsql').
     *
     * @return string The database type
     */
    public function getDbType()
    {
        return $this->dbType;
    }
}
