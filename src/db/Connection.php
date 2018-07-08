<?php
/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace RecordTracker\db;

/**
 * Class Connection. An abstract class that encapsulates the database connection and record tracking methods.
 *
 * @package RecordTracker\db
 * @author Vasilis Lourdas dev@lourdas.eu
 * @version 0.1.0
 *
 * Abstract class that encapsulates the database connection used in manipulating the record logs.
 */
abstract class Connection
{
    /**
     * @var \PDO $_connection The PDO database object used internally.
     */
    protected $_connection;

    /**
     * Returns the internal PDO database object.
     *
     * @return \PDO The PDO database object
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Insert a record log in the database.
     *
     * @param array $record This is an array that holds information about the record change. It consists of these data:
     * * ```tableName```: The database table name whose record log is saved.
     * * ```recId```: The primary record of the row whose changes are saved. This is a JSON representation of the
     *   primary key, eg. {'id': 3} for simple keys, or {'id_customer': 43, 'id_order': 5543} for composite keys.
     * * ```recType```: The type or record change. One of (**C**)reate, (**U**)update or (**D**)elete (1 character).
     * * ```byUser```: A string representing the user that created the record change. This could be a username (since
     *   they are usually unique) or a user identifier. This should uniquely identify the user that caused the change.
     * * ```oldValues```: This is an key-value based array that consists of the attribute names and their old values
     *   before the record update.
     * * ```newValues```: This is an key-value based array that consists of the attribute names and their new values
     *   after the record update.
     * @param bool $calcDiffArray If this is true, the Connection class is responsible for generating the diff
     * between the ```oldValues``` and the ```newValues``` in the ```$record``` param. If false, the caller must provide
     * ```oldValues``` and ```newValues``` as arrays containing the attributes that were changed and their old and new
     * values.
     *
     * @return mixed
     */
    public abstract function insertRecordLog($record = [], $calcDiffArray = true);

    /**
     * Returns the changes of a record as a PHP array.
     *
     * @param string $tableName The table name where the record resides
     * @param array $priKey The primary key of the record to lookup. For simple keys this could be
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
     * @return array The record log changes as a PHP array
     */
    public abstract function getRecordLogDetails($tableName = '', $priKey = []);
}
