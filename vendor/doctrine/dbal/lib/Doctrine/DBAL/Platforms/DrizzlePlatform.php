<?php
 namespace Doctrine\DBAL\Platforms; use Doctrine\DBAL\Schema\Identifier; use Doctrine\DBAL\Schema\TableDiff; use Doctrine\DBAL\Schema\Index; use Doctrine\DBAL\Schema\Table; use Doctrine\DBAL\Types\BinaryType; class DrizzlePlatform extends AbstractPlatform { public function getName() { return 'drizzle'; } public function getIdentifierQuoteCharacter() { return '`'; } public function getConcatExpression() { $args = func_get_args(); return 'CONCAT(' . join(', ', (array) $args) . ')'; } protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit) { $function = '+' === $operator ? 'DATE_ADD' : 'DATE_SUB'; return $function . '(' . $date . ', INTERVAL ' . $interval . ' ' . $unit . ')'; } public function getDateDiffExpression($date1, $date2) { return 'DATEDIFF(' . $date1 . ', ' . $date2 . ')'; } public function getBooleanTypeDeclarationSQL(array $field) { return 'BOOLEAN'; } public function getIntegerTypeDeclarationSQL(array $field) { return 'INT' . $this->_getCommonIntegerTypeDeclarationSQL($field); } protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef) { $autoinc = ''; if ( ! empty($columnDef['autoincrement'])) { $autoinc = ' AUTO_INCREMENT'; } return $autoinc; } public function getBigIntTypeDeclarationSQL(array $field) { return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSQL($field); } public function getSmallIntTypeDeclarationSQL(array $field) { return 'INT' . $this->_getCommonIntegerTypeDeclarationSQL($field); } protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed) { return $length ? 'VARCHAR(' . $length . ')' : 'VARCHAR(255)'; } protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed) { return 'VARBINARY(' . ($length ?: 255) . ')'; } protected function initializeDoctrineTypeMappings() { $this->doctrineTypeMapping = array( 'boolean' => 'boolean', 'varchar' => 'string', 'varbinary' => 'binary', 'integer' => 'integer', 'blob' => 'blob', 'decimal' => 'decimal', 'datetime' => 'datetime', 'date' => 'date', 'time' => 'time', 'text' => 'text', 'timestamp' => 'datetime', 'double' => 'float', 'bigint' => 'bigint', ); } public function getClobTypeDeclarationSQL(array $field) { return 'TEXT'; } public function getBlobTypeDeclarationSQL(array $field) { return 'BLOB'; } public function getCreateDatabaseSQL($name) { return 'CREATE DATABASE ' . $name; } public function getDropDatabaseSQL($name) { return 'DROP DATABASE ' . $name; } protected function _getCreateTableSQL($tableName, array $columns, array $options = array()) { $queryFields = $this->getColumnDeclarationListSQL($columns); if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) { foreach ($options['uniqueConstraints'] as $index => $definition) { $queryFields .= ', ' . $this->getUniqueConstraintDeclarationSQL($index, $definition); } } if (isset($options['indexes']) && ! empty($options['indexes'])) { foreach ($options['indexes'] as $index => $definition) { $queryFields .= ', ' . $this->getIndexDeclarationSQL($index, $definition); } } if (isset($options['primary']) && ! empty($options['primary'])) { $keyColumns = array_unique(array_values($options['primary'])); $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')'; } $query = 'CREATE '; if (!empty($options['temporary'])) { $query .= 'TEMPORARY '; } $query .= 'TABLE ' . $tableName . ' (' . $queryFields . ') '; $query .= $this->buildTableOptions($options); $query .= $this->buildPartitionOptions($options); $sql[] = $query; if (isset($options['foreignKeys'])) { foreach ((array) $options['foreignKeys'] as $definition) { $sql[] = $this->getCreateForeignKeySQL($definition, $tableName); } } return $sql; } private function buildTableOptions(array $options) { if (isset($options['table_options'])) { return $options['table_options']; } $tableOptions = array(); if ( ! isset($options['collate'])) { $options['collate'] = 'utf8_unicode_ci'; } $tableOptions[] = sprintf('COLLATE %s', $options['collate']); if ( ! isset($options['engine'])) { $options['engine'] = 'InnoDB'; } $tableOptions[] = sprintf('ENGINE = %s', $options['engine']); if (isset($options['auto_increment'])) { $tableOptions[] = sprintf('AUTO_INCREMENT = %s', $options['auto_increment']); } if (isset($options['comment'])) { $comment = trim($options['comment'], " '"); $tableOptions[] = sprintf("COMMENT = %s ", $this->quoteStringLiteral($comment)); } if (isset($options['row_format'])) { $tableOptions[] = sprintf('ROW_FORMAT = %s', $options['row_format']); } return implode(' ', $tableOptions); } private function buildPartitionOptions(array $options) { return (isset($options['partition_options'])) ? ' ' . $options['partition_options'] : ''; } public function getListDatabasesSQL() { return "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE CATALOG_NAME='LOCAL'"; } protected function getReservedKeywordsClass() { return 'Doctrine\DBAL\Platforms\Keywords\DrizzleKeywords'; } public function getListTablesSQL() { return "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE' AND TABLE_SCHEMA=DATABASE()"; } public function getListTableColumnsSQL($table, $database = null) { if ($database) { $database = "'" . $database . "'"; } else { $database = 'DATABASE()'; } return "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT, IS_NULLABLE, IS_AUTO_INCREMENT, CHARACTER_MAXIMUM_LENGTH, COLUMN_DEFAULT," . " NUMERIC_PRECISION, NUMERIC_SCALE, COLLATION_NAME" . " FROM DATA_DICTIONARY.COLUMNS" . " WHERE TABLE_SCHEMA=" . $database . " AND TABLE_NAME = '" . $table . "'"; } public function getListTableForeignKeysSQL($table, $database = null) { if ($database) { $database = "'" . $database . "'"; } else { $database = 'DATABASE()'; } return "SELECT CONSTRAINT_NAME, CONSTRAINT_COLUMNS, REFERENCED_TABLE_NAME, REFERENCED_TABLE_COLUMNS, UPDATE_RULE, DELETE_RULE" . " FROM DATA_DICTIONARY.FOREIGN_KEYS" . " WHERE CONSTRAINT_SCHEMA=" . $database . " AND CONSTRAINT_TABLE='" . $table . "'"; } public function getListTableIndexesSQL($table, $database = null) { if ($database) { $database = "'" . $database . "'"; } else { $database = 'DATABASE()'; } return "SELECT INDEX_NAME AS 'key_name', COLUMN_NAME AS 'column_name', IS_USED_IN_PRIMARY AS 'primary', IS_UNIQUE=0 AS 'non_unique'" . " FROM DATA_DICTIONARY.INDEX_PARTS" . " WHERE TABLE_SCHEMA=" . $database . " AND TABLE_NAME='" . $table . "'"; } public function prefersIdentityColumns() { return true; } public function supportsIdentityColumns() { return true; } public function supportsInlineColumnComments() { return true; } public function supportsViews() { return false; } public function supportsColumnCollation() { return true; } public function getDropIndexSQL($index, $table=null) { if ($index instanceof Index) { $indexName = $index->getQuotedName($this); } elseif (is_string($index)) { $indexName = $index; } else { throw new \InvalidArgumentException('DrizzlePlatform::getDropIndexSQL() expects $index parameter to be string or \Doctrine\DBAL\Schema\Index.'); } if ($table instanceof Table) { $table = $table->getQuotedName($this); } elseif (!is_string($table)) { throw new \InvalidArgumentException('DrizzlePlatform::getDropIndexSQL() expects $table parameter to be string or \Doctrine\DBAL\Schema\Table.'); } if ($index instanceof Index && $index->isPrimary()) { return $this->getDropPrimaryKeySQL($table); } return 'DROP INDEX ' . $indexName . ' ON ' . $table; } protected function getDropPrimaryKeySQL($table) { return 'ALTER TABLE ' . $table . ' DROP PRIMARY KEY'; } public function getDateTimeTypeDeclarationSQL(array $fieldDeclaration) { if (isset($fieldDeclaration['version']) && $fieldDeclaration['version'] == true) { return 'TIMESTAMP'; } return 'DATETIME'; } public function getTimeTypeDeclarationSQL(array $fieldDeclaration) { return 'TIME'; } public function getDateTypeDeclarationSQL(array $fieldDeclaration) { return 'DATE'; } public function getAlterTableSQL(TableDiff $diff) { $columnSql = array(); $queryParts = array(); if ($diff->newName !== false) { $queryParts[] = 'RENAME TO ' . $diff->getNewName()->getQuotedName($this); } foreach ($diff->addedColumns as $column) { if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) { continue; } $columnArray = $column->toArray(); $columnArray['comment'] = $this->getColumnComment($column); $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray); } foreach ($diff->removedColumns as $column) { if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) { continue; } $queryParts[] = 'DROP ' . $column->getQuotedName($this); } foreach ($diff->changedColumns as $columnDiff) { if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) { continue; } $column = $columnDiff->column; $columnArray = $column->toArray(); if ($columnArray['type'] instanceof BinaryType && $columnDiff->hasChanged('fixed') && count($columnDiff->changedProperties) === 1 ) { continue; } $columnArray['comment'] = $this->getColumnComment($column); $queryParts[] = 'CHANGE ' . ($columnDiff->getOldColumnName()->getQuotedName($this)) . ' ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray); } foreach ($diff->renamedColumns as $oldColumnName => $column) { if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) { continue; } $oldColumnName = new Identifier($oldColumnName); $columnArray = $column->toArray(); $columnArray['comment'] = $this->getColumnComment($column); $queryParts[] = 'CHANGE ' . $oldColumnName->getQuotedName($this) . ' ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray); } $sql = array(); $tableSql = array(); if ( ! $this->onSchemaAlterTable($diff, $tableSql)) { if (count($queryParts) > 0) { $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . implode(", ", $queryParts); } $sql = array_merge( $this->getPreAlterTableIndexForeignKeySQL($diff), $sql, $this->getPostAlterTableIndexForeignKeySQL($diff) ); } return array_merge($sql, $tableSql, $columnSql); } public function getDropTemporaryTableSQL($table) { if ($table instanceof Table) { $table = $table->getQuotedName($this); } elseif (!is_string($table)) { throw new \InvalidArgumentException('getDropTableSQL() expects $table parameter to be string or \Doctrine\DBAL\Schema\Table.'); } return 'DROP TEMPORARY TABLE ' . $table; } public function convertBooleans($item) { if (is_array($item)) { foreach ($item as $key => $value) { if (is_bool($value) || is_numeric($item)) { $item[$key] = ($value) ? 'true' : 'false'; } } } elseif (is_bool($item) || is_numeric($item)) { $item = ($item) ? 'true' : 'false'; } return $item; } public function getLocateExpression($str, $substr, $startPos = false) { if ($startPos == false) { return 'LOCATE(' . $substr . ', ' . $str . ')'; } return 'LOCATE(' . $substr . ', ' . $str . ', '.$startPos.')'; } public function getGuidExpression() { return 'UUID()'; } public function getRegexpExpression() { return 'RLIKE'; } } 