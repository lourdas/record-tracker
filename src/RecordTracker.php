<?php

namespace RecordTracker;

use RecordTracker\db\config\Config;
use RecordTracker\db\Connection;
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
        $config = new Config($config);
        $this->connection = new PgConnection($config);
    }

    /**
     * Insert a record log in the database. The record could be either new (create), changed (update) or old (delete).
     *
     * @param string tableName The database table name whose record log is saved.
     * @param array recId The primary key of the row whose changes are saved. This is an array. For simple keys this
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
     * @param string recType The type or record change. One of (**C**)reate, (**U**)update or (**D**)elete (1
     *     character).
     * @param string byUser A string representing the user that created the record change. This could be a username
     * (since they are usually unique) or a user identifier. This should uniquely identify the user that caused
     * the change.
     * @param array oldValues This is an key-value based array that consists of the attribute names and their old
     *     values
     *   before the record update.
     * @param array newValues This is an key-value based array that consists of the attribute names and their new
     *     values
     *   after the record update.
     * @param bool $calcDiffArray If this is true, the RecordTracker class is responsible for generating the diff
     * between the ```oldValues``` and the ```newValues``` parameters. If false, the caller must provide
     * ```oldValues``` and ```newValues``` as arrays containing the attributes that were changed and their old and new
     * values.
     *
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
        $recId = $this->connection->insertRecordLog([
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
