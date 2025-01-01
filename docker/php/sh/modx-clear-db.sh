#!/bin/bash

echo "Start cleaning DB: ${MODX_DB_NAME}..."
mysql -u"$MODX_DB_USER" -p"$MODX_DB_PASSWORD" -h"$MODX_DB_SERVER" -e "
SET FOREIGN_KEY_CHECKS = 0;
USE $MODX_DB_NAME;
SET GROUP_CONCAT_MAX_LEN = 1000000;
SET @tables = NULL;
SELECT GROUP_CONCAT('\`', table_name, '\`') INTO @tables
FROM information_schema.tables
WHERE table_schema = '$MODX_DB_NAME';
SET @sql = IFNULL(CONCAT('DROP TABLE ', @tables), 'SELECT \"No tables to drop\"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET FOREIGN_KEY_CHECKS = 1;
" || echo "Error when deleting tables from the database $MODX_DB_NAME"
echo "Finish cleaning DB"
