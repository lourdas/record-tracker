<?php
/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace RecordTracker;

use RecordTracker\db\config\Config;
use RecordTracker\db\Connection;
use RecordTracker\db\MySQLConnection;
use RecordTracker\db\PgConnection;

/**
 * Class RecordTracker
 * @package RecordTracker
 * @author Vasilis Lourdas dev@lourdas.eu
 * @version 0.1.0
 */
class RecordTracker
{
    /**
     * @var Connection $connection The database connection used internally
     */
    private $connection;

    /**
     * RecordTracker constructor. Initializes the internal database object using the values in ```$config```.
     *
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        if (!$config) {
            throw new \Exception('Required database configuration missing.');
        }
        $conf = new Config($config);
        switch ($config['dbType']) {
            case 'mysql':
                $this->connection = new MySQLConnection($conf);
                break;
            case 'pgsql':
                $this->connection = new PgConnection($conf);
                break;
            default:
                throw new \Exception('Database type \'' . $config['dbType'] . '\' not supported.');
        }
    }

    /**
     * Insert a record log in the database. The record could be either new (create), changed (update) or old (delete).
     *
     * @param string $tableName The database table name whose record log is saved.
     * @param array $recId The primary key of the row whose changes are saved. This is an array. For simple keys this
     * could be
     * ```php
     * ['id' => 3]
     * ```
     * or for composite keys, this could be
     * ```php
     * [
     *   'id_customer' => 3,
     *   'id_order' => 5543
     * ]
     * ```
     * @param string $recType The type or record change. One of (**C**)reate, (**U**)update or (**D**)elete (1
     *     character).
     * @param string $byUser A string representing the user that created the record change. This could be a username
     * (since they are usually unique) or a user identifier. This should uniquely identify the user that caused
     * the change.
     * @param array $oldValues This is an key-value based array that consists of the attribute names and their old
     *     values
     *   before the record update.
     * @param array $newValues This is an key-value based array that consists of the attribute names and their new
     *     values
     *   after the record update.
     * @param bool $calcDiffArray If this is true, the RecordTracker class is responsible for generating the diff
     * between the ```oldValues``` and the ```newValues``` parameters. If false, the caller must provide
     * ```oldValues``` and ```newValues``` as arrays containing the attributes that were changed and their old and new
     * values.
     *
     * @return array An array of identifiers for the inserted log records, this would be of the form:
     * ```php
     * [43 => [554, 555, 556]]
     * ```
     * where 43 is the master log record and 554, 555, 556 are the identifiers for each attribute change record
     * @throws \Exception
     */
    public function insertRecordLog(
        $tableName = '',
        $recId = [],
        $recType = '',
        $byUser = '',
        $oldValues = [],
        $newValues = [],
        $calcDiffArray = true
    ) {
        return $this->connection->insertRecordLog([
            'tableName' => $tableName,
            'recId' => $recId,
            'recType' => $recType,
            'byUser' => $byUser,
            'oldValues' => $oldValues,
            'newValues' => $newValues,
        ], $calcDiffArray);
    }

    /**
     * Retrieve the record changes for the row identified by ```$priKey``` in the ```$tableName``` database table as
     * a PHP array.
     *
     * @param string $tableName The table name where the requested record resides
     * @param array $priKey The primary key of the requested row. This is an array. For simple keys this
     * could be
     * ```php
     * ['id' => 3]
     * ```
     * or for composite keys, this could be
     * ```php
     * [
     *   'id_customer' => 3,
     *   'id_order' => 5543
     * ]
     * ```
     *
     * @return array A multidimensional array containing the record changes
     * @throws \Exception
     */
    public function getRecordLogDetails($tableName = '', $priKey = [])
    {
        return $this->connection->getRecordLogDetails($tableName, $priKey);
    }
}
