<?php

namespace RecordTracker\db;

abstract class Connection
{
    protected $_connection;

    /**
     * @return \PDO The PDO database object
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    public abstract function insertRecordLog($record = []);

    public abstract function getRecordLogDetails($id = []);
}
