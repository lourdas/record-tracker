CREATE TABLE "log_record" (
"id" serial8 NOT NULL,
"table_name" varchar(100) NOT NULL,
"rec_id" jsonb NOT NULL,
"ts_change" timestamp NOT NULL,
"rec_type" char(1) NOT NULL,
"by_user" varchar(100) NOT NULL,
PRIMARY KEY ("id")
)
WITHOUT OIDS;
CREATE INDEX "log_record_tablename_idx" ON "log_record" ("table_name" ASC NULLS LAST);
COMMENT ON COLUMN "log_record"."id" IS 'Table''s primary key';
COMMENT ON COLUMN "log_record"."table_name" IS 'Table name of record change';
COMMENT ON COLUMN "log_record"."rec_id" IS 'The id of the record in the table_name that was changed, stored inside a json object. For composite primary keys, store the primary key value as multiple attributes inside the json object.';
COMMENT ON COLUMN "log_record"."ts_change" IS 'Timestamp of change';
COMMENT ON COLUMN "log_record"."rec_type" IS '(I)nsert, (U)pdate or (D)elete';
COMMENT ON COLUMN "log_record"."by_user" IS 'Username that created the change';

CREATE TABLE "log_record_detail" (
"id" serial8 NOT NULL,
"id_log_record" int8 NOT NULL,
"col_name" varchar(50) NOT NULL,
"old_value" text,
"new_value" text,
PRIMARY KEY ("id")
)
WITHOUT OIDS;
CREATE INDEX "log_record_detail_colname_idx" ON "log_record_detail" ("id_log_record" ASC NULLS LAST, "col_name" ASC NULLS LAST);
COMMENT ON COLUMN "log_record"."id" IS 'Table''s primary key';
COMMENT ON COLUMN "log_record"."id_log_record" IS 'The id of the primary log record row';
COMMENT ON COLUMN "log_record_detail"."col_name" IS 'Column name of the change';
COMMENT ON COLUMN "log_record_detail"."old_value" IS 'Old value of the change';
COMMENT ON COLUMN "log_record_detail"."new_value" IS 'New value of the change';


ALTER TABLE "log_record_detail" ADD CONSTRAINT "log_record_detail_log_record_fk" FOREIGN KEY ("id_log_record") REFERENCES "log_record" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;

