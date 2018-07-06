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
     * RecordTracker constructor.
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
     * @param string $tableName
     * @param array $recId
     * @param string $recType
     * @param string $byUser
     * @param array $oldValues
     * @param array $newValues
     * @param bool $calcDiffArray
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
     * @param string $tableName
     * @param array $priKey
     *
     * @throws \Exception
     */
    public function getRecordLogDetails($tableName = '', $priKey = [])
    {
        $this->connection->getRecordLogDetails($tableName, $priKey);
    }
}
