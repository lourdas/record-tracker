<?php
/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace RecordTracker\db;

use Exception;
use RecordTracker\db\config\Config;
use PDO;
use PDOException;

/**
 * Class MySQLConnection. This instantiates a Connection object for a MySQL/MariaDB database.
 *
 * @package RecordTracker\db
 * @author Vasilis Lourdas dev@lourdas.eu
 * @version 0.1.0
 *
 * This class encapsulates a PostgreSQL database connection.
 */
class MySQLConnection extends Connection
{
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
        if (!extension_loaded('pdo_mysql')) {
            throw new \Exception('The PDO mysql extension is not loaded. The extension is required for this class.');
        }
        $this->_connection = $this->_initConnection($config);
    }

    /**
     * Initializes the PDO database connection object.
     *
     * @param Config $config Parameters used for initializing the database connection
     *
     * @return PDO The PDO database object
     * @throws Exception
     */
    protected function _initConnection(Config $config)
    {
        if (!$this->_connection) {
            try {
                /*
                 * if not provided, default to 3306 port for MySQL/MariaDB
                 */
                $port = $config->getPort() ? $config->getPort() : 3306;
                $this->_connection = new PDO('mysql:'
                    . 'host=' . $config->getHost() . ';'
                    . 'port=' . $port . ';'
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
INSERT INTO log_record(table_name, rec_id, ts_change, rec_type, by_user)
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
     * @param string $tableName
     * @param array $priKey
     *
     * @return array
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
  FROM log_record lr LEFT JOIN log_record_detail lrd ON lr."id" = lrd.id_log_record
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
        $data = [];
        foreach ($result as $v) {
            if (!isset($data['tableName'])) {
                $data['tableName'] = $v['table_name'];
            }
            if (!isset($data['priKey'])) {
                $data['priKey'] = $priKey;
            }
            if (!isset($data['records'])) {
                $data['records'] = [];
            }
            if (!isset($data['records'][$v['id_log_record']])) {
                $data['records'][$v['id_log_record']] = [];
            }
            if (!isset($data['records'][$v['id_log_record']]['recType'])) {
                $data['records'][$v['id_log_record']]['recType'] = $v['rec_type'];
            }
            if (!isset($data['records'][$v['id_log_record']]['byUser'])) {
                $data['records'][$v['id_log_record']]['byUser'] = $v['by_user'];
            }
            if (!isset($data['records'][$v['id_log_record']]['tsChange'])) {
                $data['records'][$v['id_log_record']]['tsChange'] = $v['ts_change'];
            }
            if (!isset($data['records'][$v['id_log_record']]['attributes'])) {
                $data['records'][$v['id_log_record']]['attributes'] = [];
            }
            if (!isset($data['records'][$v['id_log_record']]['attributes'][$v['col_name']])) {
                $data['records'][$v['id_log_record']]['attributes'][$v['col_name']] = [
                    'oldValue' => $v['old_value'],
                    'newValue' => $v['new_value'],
                ];
            }
        }

        return $data;
    }
}
