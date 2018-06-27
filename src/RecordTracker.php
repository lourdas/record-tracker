<?php

namespace RecordTracker;

use RecordTracker\db\config\Config;
use RecordTracker\db\PgConnection;

class RecordTracker
{
    private $connection;

    public function __construct($config = [])
    {
        if (!$config) {
            throw new \Exception('Required database configuration missing.');
        }
        $config = new Config($config);
        $this->connection = new PgConnection($config);
    }

    public function insertRecordLog(
        $tableName = '',
        $recId = [],
        $recType = '',
        $byUser = '',
        $oldValues = [],
        $newValues = []
    ) {
        $recId = $this->connection->insertRecordLog([
            'tableName' => $tableName,
            'recId' => $recId,
            'recType' => $recType,
            'byUser' => $byUser,
            'oldValues' => $oldValues,
            'newValues' => $newValues
        ]);
    }

    public function getRecordLogDetails($tableName = '', $priKey = [])
    {
        $this->connection->getRecordLogDetails($tableName, $priKey);
    }
}
