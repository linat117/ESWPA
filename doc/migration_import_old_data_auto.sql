-- Auto data migration from old DB to new DB using matching table/column names
-- SOURCE DB  : ethiosdt_nw_db   (old/live data COPY)
-- TARGET DB  : ethiosocialworks (new structure, currently empty or only test data)
--
-- Usage (recommended order):
-- 1) Make sure you already imported the NEW structure into `ethiosocialworks`.
-- 2) Run your `migration_add_null_defaults.sql` against `ethiosocialworks`
--    so that new columns default to NULL / 0 where needed.
-- 3) Ensure `ethiossocialworks` has NO dummy data you care about (truncate if needed).
-- 4) In phpMyAdmin (or CLI), select any database and execute this file.
--    It will:
--      - Disable foreign key checks on the target DB.
--      - For every table that exists in BOTH DBs:
--          * Find columns that exist in both tables.
--          * Run:
--              INSERT INTO ethiosocialworks.table (common_cols...)
--              SELECT common_cols... FROM ethiosdt_nw_db.table;
-- 5) When finished, your `ethiossocialworks` DB will contain all data from `ethiosdt_nw_db`
--    wherever tables/columns match, while keeping all new tables/columns from the new system.

SET @source_db := 'ethiosdt_nw_db';
SET @target_db := 'ethiosocialworks';

SET FOREIGN_KEY_CHECKS = 0;

-- Clean up any previous runs
DROP PROCEDURE IF EXISTS truncate_common_tables;
DROP PROCEDURE IF EXISTS migrate_common_tables;

DELIMITER $$

-- First procedure: truncate all common tables in the TARGET DB
CREATE PROCEDURE truncate_common_tables()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_table VARCHAR(64);

    DECLARE cur CURSOR FOR
        SELECT DISTINCT s.table_name
        FROM information_schema.columns s
        JOIN information_schema.columns t
          ON t.table_schema = @target_db
         AND t.table_name = s.table_name
         AND t.column_name = s.column_name
        WHERE s.table_schema = @source_db;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_table;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        -- Use DELETE instead of TRUNCATE to avoid FK restriction errors
        SET @sql := CONCAT('DELETE FROM `', @target_db, '`.`', v_table, '`;');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;

    CLOSE cur;
END$$

-- Second procedure: insert data from SOURCE DB into TARGET DB
CREATE PROCEDURE migrate_common_tables()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_table VARCHAR(64);
    DECLARE v_cols TEXT;

    -- Cursor over all tables that exist in both source and target,
    -- and build a comma-separated list of columns that exist in BOTH.
    DECLARE cur CURSOR FOR
        SELECT s.table_name,
               GROUP_CONCAT(s.column_name ORDER BY s.ordinal_position) AS col_list
        FROM information_schema.columns s
        JOIN information_schema.columns t
          ON t.table_schema = @target_db
         AND t.table_name = s.table_name
         AND t.column_name = s.column_name
        WHERE s.table_schema = @source_db
        GROUP BY s.table_name;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    -- Ensure FK checks are disabled during insert phase even if this
    -- procedure is called manually without running the full script.
    SET FOREIGN_KEY_CHECKS = 0;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_table, v_cols;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        -- Build and execute:
        -- INSERT INTO target_db.v_table (v_cols) SELECT v_cols FROM source_db.v_table;
        SET @sql := CONCAT(
            'INSERT INTO `', @target_db, '`.`', v_table, '` (', v_cols, ') ',
            'SELECT ', v_cols, ' FROM `', @source_db, '`.`', v_table, '`;'
        );

        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;

    -- Re-enable FK checks after all inserts
    SET FOREIGN_KEY_CHECKS = 1;

    CLOSE cur;
END$$

DELIMITER ;

-- 1) Truncate all common tables in TARGET
CALL truncate_common_tables();
-- 2) Insert all data from SOURCE into TARGET
CALL migrate_common_tables();

DROP PROCEDURE truncate_common_tables;
DROP PROCEDURE migrate_common_tables;

SET FOREIGN_KEY_CHECKS = 1;

