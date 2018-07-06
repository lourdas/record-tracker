<?php

namespace RecordTracker\db;

use Exception;
use RecordTracker\db\config\Config;
use PDO;
use PDOException;

/**
 * Class PgConnection
 * @package RecordTracker\db
 * @author Vasilis Lourdas dev@lourdas.eu
 *
 * This class encapsulates a PostgreSQL database connection.
 */
class PgConnection extends Connection
{
    /**
     * @var string $schema The database schema used
     */
    private $schema;

    /**
     * PgConnection constructor. Initializes the PostgreSQL database connection, using the parameters given in the
     * Config instance.
     *
     * @param Config $config Parameters used for initializing the database connection
     *
     * @throws Exception
     */
    public function __construct(Config $config)
    {
        if (!extension_loaded('pdo_pgsql')) {
            throw new \Exception('The PDO pgsql extension is not loaded.');
        }
        $this->_connection = $this->_initConnection($config);
        $this->schema = $config->getSchema();
    }

    /**
     * Initializes the PDO database connection object.
     * @param Config $config Parameters used for initializing the database connection
     *
     * @return PDO The PDO database object
     * @throws Exception
     */
    protected function _initConnection(Config $config)
    {
        if (!$this->_connection) {
            try {
                $this->_connection = new PDO('pgsql:'
                    . 'host=' . $config->getHost() . ';'
                    . 'port=' . $config->getPort() . ';'
                    . 'dbname=' . $config->getDb() . ';'
                    . 'user=' . $config->getUser() . ';'
                    . 'password=' . $config->getPassword()
                );
                $this->_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new Exception('Cannot open database connection: ' . $e->getMessage());
            }
        }

        return $this->_connection;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $record
     * @param bool $calcDiffArray
     *
     * @throws Exception
     */
    public function insertRecordLog($record = [], $calcDiffArray = true)
    {
        $attrs = ['tableName', 'recId', 'recType', 'byUser', 'oldValues', 'newValues'];
        foreach ($attrs as $attr) {
            if (!isset($record[$attr])) {
                throw new Exception('Required record log key "' . $attr . '" is missing.');
            }
            if ($attr == 'recType' && !in_array($record[$attr], ['C', 'U', 'D'])) {
                throw new Exception('Record log type "' . $record[$attr] . '" is invalid!');
            }
        }
        $masterId = null;
        $logRecordIds = [];
        $conn = $this->getConnection();
        $conn->beginTransaction();
        try {
            $ts = (new \DateTime())->format('Y-m-d H:i:s.u');
            $insertMasterLogCommand = <<<SQL
INSERT INTO {$this->schema}log_record(table_name, rec_id, ts_change, rec_type, by_user)
  VALUES (:table_name, :rec_id, :ts_change, :rec_type, :by_user)
RETURNING id
SQL;
            $stmt = $conn->prepare($insertMasterLogCommand);
            $stmt->bindValue(':table_name', $record['tableName'], PDO::PARAM_STR);
            $stmt->bindValue(':rec_id', json_encode($record['recId']), PDO::PARAM_STR);
            $stmt->bindValue(':ts_change', $ts, PDO::PARAM_STR);
            $stmt->bindValue(':rec_type', $record['recType'], PDO::PARAM_STR);
            $stmt->bindValue(':by_user', $record['byUser'], PDO::PARAM_STR);
            $stmt->execute();
            $masterId = $stmt->fetch(PDO::FETCH_NUM);
            if ($masterId === false) {
                throw new PDOException('Master log record id could not be returned, error: ' . $stmt->errorCode());
            } else {
                $masterId = $masterId[0];
                $logRecordIds[$masterId] = [];
                $insertDetailLogCommand = <<<SQL
INSERT INTO {$this->schema}log_record_detail(id_log_record, col_name, old_value, new_value)
  VALUES (:id_log_record, :col_name, :old_value, :new_value)
RETURNING id
SQL;
                $stmtDetail = $conn->prepare($insertDetailLogCommand);
                $stmtDetail->bindValue(':id_log_record', $masterId, PDO::PARAM_INT);
                if (!$calcDiffArray) {
                    switch ($record['recType']) {
                        case 'C' :
                            if (!isset($record['newValues'])) {
                                throw new PDOException('Required array for new values for create record is missing or empty.');
                            }
                            break;
                        case 'U' :
                            if (!isset($record['oldValues'])) {
                                throw new PDOException('Required array for old values for update record is missing or empty.');
                            }
                            if (!isset($record['newValues'])) {
                                throw new PDOException('Required array for new values for update record is missing or empty.');
                            }
                            break;
                        case 'D' :
                            if (!isset($record['oldValues'])) {
                                throw new PDOException('Required array for old values for delete record is missing or empty.');
                            }
                            break;
                    }
                    $valuesIter = ($record['recType'] == 'C' ? $record['newValues'] : $record['oldValues']);
                    foreach ($valuesIter as $k => $v) {
                        $stmtDetail->bindValue(':col_name', $k, PDO::PARAM_STR);
                        switch ($record['recType']) {
                            case 'C' :
                                $stmtDetail->bindValue(':old_value', null);
                                $stmtDetail->bindValue(':new_value', $v, PDO::PARAM_STR);
                                break;
                            case 'U' :
                                if (!isset($record['newValues'][$k])) {
                                    throw new PDOException('Required changed attribute not present in array with new values.');
                                }
                                $stmtDetail->bindValue(':old_value', $v, PDO::PARAM_STR);
                                $stmtDetail->bindValue(':new_value', $record['newValues'][$k], PDO::PARAM_STR);
                                break;
                            case 'D' :
                                $stmtDetail->bindValue(':old_value', $v, PDO::PARAM_STR);
                                $stmtDetail->bindValue(':new_value', null);
                                break;
                        }
                        $stmtDetail->execute();
                        $detailLogId = $stmtDetail->fetch(PDO::FETCH_NUM);
                        if ($detailLogId === false) {
                            throw new PDOException('Detail log record id could not be returned, error: '
                                . $stmtDetail->errorCode());
                        }
                        $logRecordIds[$masterId][] = $detailLogId[0];
                    }
                } else {
                    $diffOldNew = array_diff_key($record['oldValues'], $record['newValues']);
                    $diffNewOld = array_diff_key($record['newValues'], $record['oldValues']);
                    $allKeys = array_keys(array_merge($diffOldNew, $diffNewOld, $record['oldValues']));
                    $changedKeys = [];
                    foreach ($allKeys as $k) {
                        $oldValue = (isset($record['oldValues'][$k]) ? $record['oldValues'][$k] : null);
                        $newValue = (isset($record['newValues'][$k]) ? $record['newValues'][$k] : null);
                        if ($oldValue != $newValue) {
                            $stmtDetail->bindValue(':col_name', $k, PDO::PARAM_STR);
                            switch ($record['recType']) {
                                case 'C' :
                                    $stmtDetail->bindValue(':old_value', null);
                                    $stmtDetail->bindValue(':new_value', $newValue, PDO::PARAM_STR);
                                    break;
                                case 'U' :
                                    $stmtDetail->bindValue(':old_value', $oldValue, PDO::PARAM_STR);
                                    $stmtDetail->bindValue(':new_value', $newValue, PDO::PARAM_STR);
                                    break;
                                case 'D' :
                                    $stmtDetail->bindValue(':old_value', $oldValue, PDO::PARAM_STR);
                                    $stmtDetail->bindValue(':new_value', null);
                                    break;
                            }
                            $stmtDetail->execute();
                            $detailLogId = $stmtDetail->fetch(PDO::FETCH_NUM);
                            if ($detailLogId === false) {
                                throw new PDOException('Detail log record id could not be returned, error: '
                                    . $stmtDetail->errorCode());
                            }
                        }
                    }
                }
            }
            $conn->commit();
        } catch (PDOException $e) {
            $conn->rollBack();
            throw new \Exception($e->getMessage());
        }

        return $logRecordIds;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function getRecordLogDetails($tableName = '', $priKey = [])
    {
        if (!$tableName) {
            throw new \Exception('Required parameter for table name missing.');
        }
        if (preg_match('/^([a-zA-Z_]+\.)?[a-zA-Z_]+$/', $tableName) !== 1) {
            throw new \Exception('Invalid identifier for table name.');
        }
        if (!$priKey) {
            throw new \Exception('Required parameter for primary key missing.');
        }
        $sqlSelectCommand = <<<SQL
SELECT *
  FROM {$this->schema}log_record lr LEFT JOIN {$this->schema}.log_record_detail lrd ON lr."id" = lrd.id_log_record
  WHERE table_name = :table_name AND lr.rec_id=JSONB_BUILD_OBJECT(%param%)
  ORDER BY lr.ts_change, lr.id
SQL;
        $param = '';
        foreach ($priKey as $k => $v) {
            if (preg_match('/^\d{1,}$/', $v) === 1) {
                $param .= '\'' . $k . '\', ' . $v;
            } else {
                $param .= '\'' . $k . '\', \'' . $v . '\'';
            }
        }
        $conn = $this->getConnection();
        $sqlSelectCommand = str_replace('%param%', $param, $sqlSelectCommand);
        $stmt = $conn->prepare($sqlSelectCommand);
        $stmt->bindValue(':table_name', $tableName, \PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
