CREATE TABLE `log_record` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Table''s primary key',
  `table_name` varchar(100) NOT NULL COMMENT 'Table name of record change',
  `rec_id` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'The id of the record in the table_name that was changed, stored inside a json object. For composite primary keys, store the primary key value as multiple attributes inside the json object.',
  `ts_change` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Timestamp of change',
  `rec_type` char(1) NOT NULL COMMENT '(I)nsert, (U)pdate or (D)elete',
  `by_user` varchar(100) NOT NULL COMMENT 'Username that created the change',
  PRIMARY KEY (`id`) USING BTREE,
  CHECK (JSON_VALID(`rec_id`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `log_record_detail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Table''''s primary key',
  `id_log_record` bigint(20) unsigned NOT NULL COMMENT 'The id of the primary log record row',
  `col_name` varchar(50) NOT NULL COMMENT 'Column name of the change',
  `old_value` mediumtext DEFAULT NULL COMMENT 'Old value of the change',
  `new_value` mediumtext DEFAULT NULL COMMENT 'New value of the change',
  PRIMARY KEY (`id`),
  KEY `log_record_detail_colname_idx` (`id_log_record`,`col_name`),
  CONSTRAINT `log_record_detail_log_record_fk` FOREIGN KEY (`id_log_record`) REFERENCES `log_record` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
