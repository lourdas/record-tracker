<?php

namespace RecordTracker\db;

use Exception;
use RecordTracker\db\config\Config;
use PDO;
use PDOException;

class PgConnection extends Connection
{
    private $schema;

    public function __construct(Config $config)
    {
        if (!extension_loaded('pdo_pgsql')) {
            throw new \Exception('The PDO pgsql extension is not loaded.');
        }
        $this->_connection = $this->_initConnection($config);
        $this->schema = $config->getSchema();
    }

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

    public function insertRecordLog($record = [])
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
            $ts = (new \DateTime())->format('Y-m-d H:i:s');
            $insertMasterLogCommand = <<<SQL
INSERT INTO {$this->schema}.log_record(table_name, rec_id, ts_change, rec_type, by_user)
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
INSERT INTO {$this->schema}.log_record_detail(id_log_record, col_name, old_value, new_value)
  VALUES (:id_log_record, :col_name, :old_value, :new_value)
RETURNING id
SQL;
                $stmtDetail = $conn->prepare($insertDetailLogCommand);
                $stmtDetail->bindValue(':id_log_record', $masterId, PDO::PARAM_INT);
                if (!$record['oldValues']) {
                    throw new \PDOException('Required old values array is missing or empty.');
                }
                foreach ($record['oldValues'] as $k => $v) {
                    $stmtDetail->bindValue(':col_name', $k, PDO::PARAM_STR);
                    $stmtDetail->bindValue(':old_value', $v, PDO::PARAM_STR);
                    if (!isset($record['newValues'][$k]) && $record['recType'] == 'U') {
                        throw new PDOException('Required changed attribute not present in array with new values.');
                    }
                    $newValue = isset($record['newValues'][$k]) ? $record['newValues'][$k] : null;
                    $stmtDetail->bindValue(':new_value', $newValue, PDO::PARAM_STR);
                    $stmtDetail->execute();
                    $detailLogId = $stmtDetail->fetch(PDO::FETCH_NUM);
                    if ($detailLogId === false) {
                        throw new PDOException('Detail log record id could not be returned, error: '
                            . $stmtDetail->errorCode());
                    }
                    $logRecordIds[$masterId][] = $detailLogId[0];
                }
            }
            $conn->commit();
        } catch (PDOException $e) {
            $conn->rollBack();
            throw new \Exception($e->getMessage());
        }

        return $logRecordIds;
    }

    public function getRecordLogDetails($id = [])
    {
        
    }
}
