<?php

namespace DreamFactory\Core\Informix\Database\Schema;

use DreamFactory\Core\Database\Schema\ColumnSchema;
use DreamFactory\Core\Database\Schema\FunctionSchema;
use DreamFactory\Core\Database\Schema\ParameterSchema;
use DreamFactory\Core\Database\Schema\ProcedureSchema;
use DreamFactory\Core\Database\Schema\RoutineSchema;
use DreamFactory\Core\Database\Schema\TableSchema;
use DreamFactory\Core\Enums\DbSimpleTypes;
use DreamFactory\Core\SqlDb\Database\Schema\SqlSchema;

/**
 * Schema is the class for retrieving metadata information from a IBM DB2 database.
 */
class InformixSchema extends SqlSchema
{
    /**
     * @const string Quoting characters
     */
    const LEFT_QUOTE_CHARACTER = '';

    /**
     * @const string Quoting characters
     */
    const RIGHT_QUOTE_CHARACTER = '';

    public static function isUndiscoverableType($type)
    {
        switch ($type) {
            case DbSimpleTypes::TYPE_TIMESTAMP:
                return true;
        }

        return parent::isUndiscoverableType($type);
    }

    protected function translateSimpleColumnTypes(array &$info)
    {
        // override this in each schema class
        $type = (isset($info['type'])) ? $info['type'] : null;
        switch (strtolower($type)) {
            // some types need massaging, some need other required properties
            case 'pk':
            case DbSimpleTypes::TYPE_ID:
                $info['type'] = 'serial';
                $info['allow_null'] = false;
                $info['auto_increment'] = true;
                $info['is_primary_key'] = true;
                break;
            case DbSimpleTypes::TYPE_BIG_ID:
                $info['type'] = 'bigserial';
                $info['allow_null'] = false;
                $info['auto_increment'] = true;
                $info['is_primary_key'] = true;
                break;

            case 'fk':
            case DbSimpleTypes::TYPE_REF:
                $info['type'] = 'integer';
                $info['is_foreign_key'] = true;
                // check foreign tables
                break;

            case DbSimpleTypes::TYPE_TIME:
                $info['type'] = 'datetime hour to fraction';
                break;

            case DbSimpleTypes::TYPE_DATETIME:
            case DbSimpleTypes::TYPE_TIMESTAMP:
                $info['type'] = 'datetime year to fraction';
                break;
            case DbSimpleTypes::TYPE_TIMESTAMP_ON_CREATE:
                $info['type'] = 'datetime year to fraction';
                $info['allow_null'] = false;
                $default = (isset($info['default'])) ? $info['default'] : null;
                if (!isset($default)) {
                    $info['default'] = 'current';
                }
                break;
            case DbSimpleTypes::TYPE_TIMESTAMP_ON_UPDATE:
                $info['type'] = 'datetime year to fraction';
                $info['allow_null'] = false;
                $default = (isset($info['default'])) ? $info['default'] : null;
                if (!isset($default)) {
                    $info['default'] = 'current';
                }
                break;

            case DbSimpleTypes::TYPE_USER_ID:
            case DbSimpleTypes::TYPE_USER_ID_ON_CREATE:
            case DbSimpleTypes::TYPE_USER_ID_ON_UPDATE:
                $info['type'] = 'integer';
                break;

            case DbSimpleTypes::TYPE_FLOAT:
                $info['type'] = 'smallfloat';
                break;

            case DbSimpleTypes::TYPE_DOUBLE:
                $info['type'] = 'float';
                break;

            case DbSimpleTypes::TYPE_MONEY:
                $info['type'] = 'money';
                $info['type_extras'] = '(19,4)';
                break;

            case DbSimpleTypes::TYPE_BOOLEAN:
                $info['type'] = 'boolean';
                $default = (isset($info['default'])) ? $info['default'] : null;
                if (isset($default)) {
                    // convert to 't' or 'f', where necessary
                    $info['default'] = (to_bool($default) ? "'t'" : "'f'");
                }
                break;

            case DbSimpleTypes::TYPE_STRING:
                $fixed =
                    (isset($info['fixed_length'])) ? filter_var($info['fixed_length'], FILTER_VALIDATE_BOOLEAN) : false;
                $national =
                    (isset($info['supports_multibyte'])) ? filter_var($info['supports_multibyte'],
                        FILTER_VALIDATE_BOOLEAN) : false;
                if ($fixed) {
                    $info['type'] = ($national) ? 'nchar' : 'char';
                } elseif ($national) {
                    $info['type'] = 'nvarchar';
                } else {
                    $info['type'] = 'varchar';
                }
                break;

            case DbSimpleTypes::TYPE_TEXT:
                $info['type'] = 'lvarchar';
                break;

            case DbSimpleTypes::TYPE_BINARY:
                $fixed =
                    (isset($info['fixed_length'])) ? filter_var($info['fixed_length'], FILTER_VALIDATE_BOOLEAN) : false;
                $info['type'] = ($fixed) ? 'byte' : 'blob';
                break;
        }
    }

    protected function validateColumnSettings(array &$info)
    {
        // override this in each schema class
        $type = (isset($info['type'])) ? $info['type'] : null;
        switch (strtolower($type)) {
            // some types need massaging, some need other required properties
            case 'smallint':
            case 'int':
            case 'bigint':
                $default = (isset($info['default'])) ? $info['default'] : null;
                if (isset($default) && is_numeric($default)) {
                    $info['default'] = intval($default);
                }
                break;

            case 'decimal':
            case 'numeric':
            case 'real':
            case 'float':
            case 'double':
                if (!isset($info['type_extras'])) {
                    $length =
                        (isset($info['length']))
                            ? $info['length']
                            : ((isset($info['precision'])) ? $info['precision']
                            : null);
                    if (!empty($length)) {
                        $scale =
                            (isset($info['decimals']))
                                ? $info['decimals']
                                : ((isset($info['scale'])) ? $info['scale']
                                : null);
                        $info['type_extras'] = (!empty($scale)) ? "($length,$scale)" : "($length)";
                    }
                }

                $default = (isset($info['default'])) ? $info['default'] : null;
                if (isset($default) && is_numeric($default)) {
                    $info['default'] = floatval($default);
                }
                break;

            case 'character':
            case 'graphic':
            case 'binary':
            case 'varchar':
            case 'varbinary':
            case 'clob':
            case 'dbclob':
            case 'blob':
                $length = (isset($info['length'])) ? $info['length'] : ((isset($info['size'])) ? $info['size'] : 255);
                if (isset($length)) {
                    $info['type_extras'] = "($length)";
                }
                break;

            case 'time':
            case 'timestamp':
            case 'datetime':
                $default = (isset($info['default'])) ? $info['default'] : null;
                if ('0000-00-00 00:00:00' == $default) {
                    // read back from MySQL has formatted zeros, can't send that back
                    $info['default'] = 0;
                }

                $length = (isset($info['length'])) ? $info['length'] : ((isset($info['size'])) ? $info['size'] : null);
                if (isset($length)) {
                    $info['type_extras'] = "($length)";
                }
                break;
        }
    }

    /**
     * @param array $info
     *
     * @return string
     * @throws \Exception
     */
    protected function buildColumnDefinition(array $info)
    {
        $type = (isset($info['type'])) ? $info['type'] : null;
        $auto = (isset($info['auto_increment'])) ? filter_var($info['auto_increment'], FILTER_VALIDATE_BOOLEAN) : false;
        if ($auto) {
            switch ($type) {
                case 'integer':
                    $type = 'serial';
                    break;
            }
        }

        $typeExtras = (isset($info['type_extras'])) ? $info['type_extras'] : null;

        $definition = $type . $typeExtras;

        $allowNull = (isset($info['allow_null'])) ? filter_var($info['allow_null'], FILTER_VALIDATE_BOOLEAN) : false;
        $definition .= ($allowNull) ? ' NULL' : ' NOT NULL';

        $default = (isset($info['default'])) ? $info['default'] : null;
        if (isset($default)) {
            $quoteDefault =
                (isset($info['quote_default'])) ? filter_var($info['quote_default'], FILTER_VALIDATE_BOOLEAN) : false;
            if ($quoteDefault) {
                $default = "'" . $default . "'";
            }

            if ('generated by default for each row on update as row change timestamp' === $default) {
                $definition .= ' ' . $default;
            } else {
                $definition .= ' DEFAULT ' . $default;
            }
        }

        if (isset($info['is_primary_key']) && filter_var($info['is_primary_key'], FILTER_VALIDATE_BOOLEAN)) {
            $definition .= ' PRIMARY KEY';
        } elseif (isset($info['is_unique']) && filter_var($info['is_unique'], FILTER_VALIDATE_BOOLEAN)) {
            $definition .= ' UNIQUE';
        }

        return $definition;
    }

    public function requiresCreateIndex($unique = false, $on_create_table = false)
    {
        return !($unique && $on_create_table);
    }

    /**
     * @inheritdoc
     */
    protected function loadTableColumns(TableSchema $table)
    {
        $params = [':id' => $table->id];

        $sql = <<<MYSQL
SELECT sc.colname, sc.colno, sc.coltype, sc.collength, sd.type defaulttype, sd.default, 
CASE 
    WHEN sc.coltype IN (0,256)  THEN 'char' 
    WHEN sc.coltype IN (1,257)  THEN 'smallint' 
    WHEN sc.coltype IN (2,258)  THEN 'integer' 
    WHEN sc.coltype IN (3,259)  THEN 'float' 
    WHEN sc.coltype IN (4,260)  THEN 'smallfloat' 
    WHEN sc.coltype IN (5,261)  THEN 'decimal' 
    WHEN sc.coltype IN (6,262)  THEN 'serial' 
    WHEN sc.coltype IN (7,263)  THEN 'date' 
    WHEN sc.coltype IN (8,264)  THEN 'money' 
    WHEN sc.coltype IN (9,265)  THEN 'null' 
    WHEN sc.coltype IN (10,266) THEN 'datetime' 
    WHEN sc.coltype IN (11,267) THEN 'byte' 
    WHEN sc.coltype IN (12,268) THEN 'text' 
    WHEN sc.coltype IN (13,269) THEN 'varchar' 
    WHEN sc.coltype IN (14,270) THEN 'interval' 
    WHEN sc.coltype IN (15,271) THEN 'nchar' 
    WHEN sc.coltype IN (16,272) THEN 'nvarchar' 
    WHEN sc.coltype IN (17,273) THEN 'int8' 
    WHEN sc.coltype IN (18,274) THEN 'serial8' 
    WHEN sc.coltype IN (19,275) THEN 'set' 
    WHEN sc.coltype IN (20,276) THEN 'multiset' 
    WHEN sc.coltype IN (21,277) THEN 'list' 
    WHEN sc.coltype IN (22,278) THEN 'row' 
    WHEN sc.coltype IN (23,279) THEN 'collection' 
    WHEN sc.coltype IN (43,299) THEN 'lvarchar' 
    WHEN sc.coltype IN (45,301) THEN 'boolean' 
    WHEN sc.coltype IN (52,308) THEN 'bigint' 
    WHEN sc.coltype IN (53,309) THEN 'bigserial' 
    ELSE 
        CASE 
            WHEN (sc.extended_id > 0) THEN 
                (SELECT LOWER(name) FROM sysxtdtypes WHERE 
                    extended_id = sc.extended_id) 
            ELSE 'unknown'
        END 
END typename, 
CASE 
    WHEN (sc.coltype IN (13,269,16,272)) THEN  
        CASE 
            WHEN (sc.collength > 0) THEN MOD(sc.collength,256)::INT 
            ELSE MOD(sc.collength+65536,256)::INT 
        END 
    WHEN (sc.coltype IN (10,266,14,270)) THEN  
        (sc.collength / 256)::INT 
    ELSE 
        sc.collength 
END length, 
CASE 
    WHEN (sc.coltype IN (13,269,16,272)) THEN  
        CASE 
            WHEN (sc.collength > 0) THEN (sc.collength/256)::INT 
            ELSE ((65536+sc.collength)/256)::INT 
        END 
    ELSE 
        NULL 
END minlength, 
CASE 
    WHEN (sc.coltype IN (5,261,8,264) AND (sc.collength / 256) >= 1) THEN
        (sc.collength / 256)::INT  
    ELSE 
        NULL 
END precision, 
CASE 
    WHEN (sc.coltype IN (5,261,8,264) AND (MOD(sc.collength, 256) <> 255)) THEN
        MOD(sc.collength, 256)::INT  
    ELSE 
        NULL 
END scale, 
CASE 
    WHEN (sc.coltype IN (10,266,14,270)) THEN 
        (MOD(sc.collength,256) / 16)::INT 
    ELSE 
        NULL 
END first_qualifier, 
CASE 
    WHEN (sc.coltype IN (10,266,14,270)) THEN  
        MOD(MOD(sc.collength,256), 16)::INT 
    ELSE 
        NULL 
END last_qualifier, 
CASE  
    WHEN (sc.coltype < 256) THEN 'Y' 
    WHEN (sc.coltype BETWEEN 256 AND 309) THEN 'N' 
    ELSE 
        NULL 
END nulls 
FROM syscolumns sc 
LEFT OUTER JOIN sysdefaults sd ON (sc.tabid = sd.tabid AND sc.colno = sd.colno AND sd.class = 'T' AND sd.type = 'L') 
WHERE sc.tabid = :id 
MYSQL;

        $columns = $this->connection->select($sql, $params);

        foreach ($columns as $column) {
            $column = array_change_key_case((array)$column, CASE_LOWER);
            $c = new ColumnSchema(['name' => $column['colname']]);
            $c->quotedName = $this->quoteColumnName($c->name);
            $c->allowNull = ($column['nulls'] == 'Y');
            $c->isPrimaryKey = array_get($column, 'is_primary_key', false);
            $c->isUnique = array_get($column, 'is_unique', false);
            $c->dbType = $column['typename'];
            $c->size = isset($column['length']) ? intval($column['length']) : null;
            $c->precision = isset($column['precision']) ? intval($column['precision']) : null;
            $c->scale = isset($column['scale']) ? intval($column['scale']) : null;
            $c->autoIncrement = (false !== strpos($c->dbType, 'serial'));

            $c->fixedLength = $this->extractFixedLength($c->dbType);
            $c->supportsMultibyte = $this->extractMultiByteSupport($c->dbType);
            $this->extractType($c, $c->dbType);
            switch ($c->type) {
                case DbSimpleTypes::TYPE_DATETIME:
                    $firstQualifier = (int)array_get($column, 'first_qualifier');
                    $lastQualifier = (int)array_get($column, 'last_qualifier');
                    if ($firstQualifier >= 6) {
                        $c->type = DbSimpleTypes::TYPE_TIME;
                    } elseif ($lastQualifier <= 4) {
                        $c->type = DbSimpleTypes::TYPE_DATE;
                    }
                    if ($lastQualifier > 10) {
                        $c->precision = $lastQualifier - 10;
                    }
                    break;
            }

            if (is_string($column['default'])) {
                $column['default'] = trim($column['default'], '\' ');
            }

            $this->extractDefault($c, $column['default']);

            if ($c->isPrimaryKey) {
                if ($c->autoIncrement) {
                    $table->sequenceName = array_get($column, 'sequence', $c->name);
                    if ((DbSimpleTypes::TYPE_INTEGER === $c->type)) {
                        $c->type = DbSimpleTypes::TYPE_ID;
                    }
                }
                $table->addPrimaryKey($c->name);
            }
            $table->addColumn($c);
        }

        return $columns;
    }

    /**
     * @inheritdoc
     */
    protected function getTableConstraints($schema = '')
    {
        if (is_array($schema)) {
            $schema = implode("','", $schema);
        }

        $sql = <<<SQL
SELECT scon.constrname constraint_name, scon.constrtype constraint_type, 
st.tabname table_name, RTRIM(st.owner) table_schema, 
sc1.colname column_name1, sc2.colname column_name2, sc3.colname column_name3, sc4.colname column_name4,
rst.tabname referenced_table_name, RTRIM(rst.owner) referenced_table_schema, 
rsc1.colname referenced_column_name1, rsc2.colname referenced_column_name2, 
rsc3.colname referenced_column_name3, rsc4.colname referenced_column_name4, 
sref.updrule update_rule, sref.delrule delete_rule
FROM sysconstraints scon 
INNER JOIN systables st ON st.tabid = scon.tabid
INNER JOIN sysindexes si ON scon.idxname = si.idxname 
LEFT OUTER JOIN syscolumns sc1 ON (ABS(si.part1) = sc1.colno AND si.tabid = sc1.tabid) 
LEFT OUTER JOIN syscolumns sc2 ON (ABS(si.part2) = sc2.colno AND si.tabid = sc2.tabid) 
LEFT OUTER JOIN syscolumns sc3 ON (ABS(si.part3) = sc3.colno AND si.tabid = sc3.tabid) 
LEFT OUTER JOIN syscolumns sc4 ON (ABS(si.part4) = sc4.colno AND si.tabid = sc4.tabid) 
LEFT OUTER JOIN sysreferences sref ON scon.constrid = sref.constrid 
LEFT OUTER JOIN systables rst ON sref.ptabid = rst.tabid 
LEFT OUTER JOIN sysconstraints rscon ON sref.primary = rscon.constrid 
LEFT OUTER JOIN sysindexes rsi ON rscon.idxname = rsi.idxname 
LEFT OUTER JOIN syscolumns rsc1 ON (ABS(rsi.part1) = rsc1.colno AND rsi.tabid = rsc1.tabid)
LEFT OUTER JOIN syscolumns rsc2 ON (ABS(rsi.part2) = rsc2.colno AND rsi.tabid = rsc2.tabid)
LEFT OUTER JOIN syscolumns rsc3 ON (ABS(rsi.part3) = rsc3.colno AND rsi.tabid = rsc3.tabid)
LEFT OUTER JOIN syscolumns rsc4 ON (ABS(rsi.part4) = rsc4.colno AND rsi.tabid = rsc4.tabid)
WHERE scon.owner IN ('{$schema}');
SQL;

        $results = $this->connection->select($sql);
        $constraints = [];
        foreach ($results as $row) {
            $row = array_change_key_case((array)$row, CASE_LOWER);
            $ts = strtolower($row['table_schema']);
            $tn = strtolower($row['table_name']);
            $cn = strtolower($row['constraint_name']);
            if ('R' === $row['constraint_type']) {
                $row['constraint_type'] = 'foreign key';
            }
            $cols = [];
            $refCols = [];
            for ($i = 1; $i <= 16; $i++) {
                if (isset($row['column_name' . $i])) {
                    $cols[] = $row['column_name' . $i];
                    unset($row['column_name'.$i]);
                }
                if (isset($row['referenced_column_name' . $i])) {
                    $refCols[] = $row['referenced_column_name' . $i];
                    unset($row['referenced_column_name'.$i]);
                }
            }
            $row['column_name'] = $cols;
            if (!empty($refCols)) {
                $row['referenced_column_name'] = $refCols;
            }
            $constraints[$ts][$tn][$cn] = $row;
        }

        return $constraints;
    }

    public function getSchemas()
    {
        $sql = <<<MYSQL
SELECT USERNAME FROM SYSUSERS WHERE USERTYPE != 'X' ORDER BY USERNAME;
MYSQL;

        $rows = array_map('trim', $this->selectColumn($sql));

        return $rows;
    }

    /**
     * @inheritdoc
     */
    protected function getTableNames($schema = '')
    {
        $sql = <<<MYSQL
SELECT OWNER, TABNAME, TABID FROM SYSTABLES WHERE TABTYPE = 'T'
MYSQL;
        if (!empty($schema)) {
            $sql .= <<<MYSQL
  AND OWNER=:schema
MYSQL;
        }
        $sql .= <<<MYSQL
  ORDER BY TABNAME;
MYSQL;

        $params = (!empty($schema)) ? [':schema' => $schema] : [];
        $rows = $this->connection->select($sql, $params);

        $names = [];
        foreach ($rows as $row) {
            $row = array_change_key_case((array)$row, CASE_UPPER);
            $id = isset($row['TABID']) ? intval($row['TABID']) : null;
            $schemaName = trim(isset($row['OWNER']) ? $row['OWNER'] : '');
            $resourceName = trim(isset($row['TABNAME']) ? $row['TABNAME'] : '');
            $internalName = $schemaName . '.' . $resourceName;
            $name = $resourceName;
            $quotedName = $this->quoteTableName($schemaName) . '.' . $this->quoteTableName($resourceName);;
            $settings = compact('id', 'schemaName', 'resourceName', 'name', 'internalName', 'quotedName');
            $names[strtolower($name)] = new TableSchema($settings);
        }

        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function getViewNames($schema = '')
    {
        $sql = <<<MYSQL
SELECT OWNER, TABNAME, TABID FROM SYSTABLES WHERE TABTYPE = 'V'
MYSQL;
        if (!empty($schema)) {
            $sql .= <<<MYSQL
  AND OWNER=:schema
MYSQL;
        }
        $sql .= <<<MYSQL
  ORDER BY TABNAME;
MYSQL;

        $params = (!empty($schema)) ? [':schema' => $schema] : [];
        $rows = $this->connection->select($sql, $params);

        $names = [];
        foreach ($rows as $row) {
            $row = array_change_key_case((array)$row, CASE_UPPER);
            $id = isset($row['TABID']) ? intval($row['TABID']) : null;
            $schemaName = trim(isset($row['OWNER']) ? $row['OWNER'] : '');
            $resourceName = trim(isset($row['TABNAME']) ? $row['TABNAME'] : '');
            $internalName = $schemaName . '.' . $resourceName;
            $name = $resourceName;
            $quotedName = $this->quoteTableName($schemaName) . '.' . $this->quoteTableName($resourceName);;
            $settings = compact('id', 'schemaName', 'resourceName', 'name', 'internalName', 'quotedName');
            $settings['isView'] = true;
            $names[strtolower($name)] = new TableSchema($settings);
        }

        return $names;
    }

    /**
     * Resets the sequence value of a table's primary key.
     * The sequence will be reset such that the primary key of the next new row inserted
     * will have the specified value or 1.
     *
     * @param TableSchema $table    the table schema whose primary key sequence will be reset
     * @param mixed       $value    the value for the primary key of the next new row inserted. If this is not set,
     *                              the next new row's primary key will have a value 1.
     */
    public function resetSequence($table, $value = null)
    {
        if ($table->sequenceName !== null &&
            is_string($table->primaryKey) &&
            $table->getColumn($table->primaryKey)->autoIncrement
        ) {
            if ($value === null) {
                $value = $this->selectValue("SELECT MAX({$table->primaryKey}) FROM {$table->quotedName}") + 1;
            } else {
                $value = (int)$value;
            }

            $this->connection
                ->statement("ALTER TABLE {$table->quotedName} ALTER COLUMN {$table->primaryKey} RESTART WITH $value");
        }
    }

    /**
     * {@InheritDoc}
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        if ('CASCADE' !== strtoupper($delete)) {
            $delete = null; // only CASCADE supported
        }

        $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($columns as $i => $col) {
            $columns[$i] = $this->quoteColumnName($col);
        }
        $refColumns = preg_split('/\s*,\s*/', $refColumns, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($refColumns as $i => $col) {
            $refColumns[$i] = $this->quoteColumnName($col);
        }
        $sql =
            'ALTER TABLE ' .
            $this->quoteTableName($table) .
            ' ADD CONSTRAINT' .
            ' FOREIGN KEY (' . implode(', ', $columns) . ')' .
            ' REFERENCES ' . $this->quoteTableName($refTable) . ' (' . implode(', ', $refColumns) . ')' .
            (($delete !== null) ? ' ON DELETE ' . $delete : '') .
            ' CONSTRAINT ' . $this->quoteColumnName($name);

        return $sql;
    }

    /**
     * Builds a SQL statement for truncating a DB table.
     *
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for truncating a DB table.
     */
    public function truncateTable($table)
    {
        return "TRUNCATE TABLE " . $this->quoteTableName($table) . " IMMEDIATE ";
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     *
     * @param string $table      the table whose column is to be changed. The table name will be properly quoted by the
     *                           method.
     * @param string $column     the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $definition the new column type. The {@link getColumnType} method will be invoked to convert
     *                           abstract column type (if any) into the physical one. Anything that is not recognized
     *                           as abstract type will be kept in the generated SQL. For example, 'string' will be
     *                           turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not
     *                           null'.
     *
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn($table, $column, $definition)
    {
        $allowNullNewType = !preg_match("/not +null/i", $definition);

        $definition = preg_replace("/ +(not)? *null/i", "", $definition);

        $sql = <<<MYSQL
ALTER TABLE $table ALTER COLUMN {$this->quoteColumnName($column)} SET DATA TYPE {$this->getColumnType($definition)}
MYSQL;

        if ($allowNullNewType) {
            $sql .= ' ALTER COLUMN ' . $this->quoteColumnName($column) . 'DROP NOT NULL';
        } else {
            $sql .= ' ALTER COLUMN ' . $this->quoteColumnName($column) . 'SET NOT NULL';
        }

        return $sql;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultSchema()
    {
        return $this->getUserName();
    }

    protected function getRoutineNames($type, $schema = '')
    {
        $bindings = [':type' => (('PROCEDURE' === $type) ? 't' : 'f')];
        $where = "ISPROC = :type";
        if (!empty($schema)) {
            $where .= ' AND OWNER = :schema';
            $bindings[':schema'] = $schema;
        }

        $sql = <<<MYSQL
SELECT PROCNAME, PROCID FROM SYSPROCEDURES WHERE {$where}
MYSQL;
        $rows = $this->connection->select($sql, $bindings);

        $names = [];
        foreach ($rows as $row) {
            $row = array_change_key_case((array)$row, CASE_UPPER);
            $id = isset($row['PROCID']) ? intval($row['PROCID']) : null;
            $resourceName = array_get($row, 'PROCNAME');
            $schemaName = $schema;
            $internalName = $schemaName . '.' . $resourceName;
            $name = $resourceName;
            $quotedName = $this->quoteTableName($schemaName) . '.' . $this->quoteTableName($resourceName);
            $settings = compact('id', 'schemaName', 'resourceName', 'name', 'internalName', 'quotedName');
            $names[strtolower($name)] =
                ('PROCEDURE' === $type) ? new ProcedureSchema($settings) : new FunctionSchema($settings);
        }

        return $names;
    }

    protected function loadParameters(RoutineSchema $holder)
    {
        $bindings = [':id' => $holder->id];
        $sql = <<<MYSQL
SELECT sc.paramid, sc.paramname, sc.paramlen, sd.type defaulttype, sd.default,
CASE 
    WHEN sc.paramtype IN (0,256)  THEN 'char' 
    WHEN sc.paramtype IN (1,257)  THEN 'smallint' 
    WHEN sc.paramtype IN (2,258)  THEN 'integer' 
    WHEN sc.paramtype IN (3,259)  THEN 'float' 
    WHEN sc.paramtype IN (4,260)  THEN 'smallfloat' 
    WHEN sc.paramtype IN (5,261)  THEN 'decimal' 
    WHEN sc.paramtype IN (6,262)  THEN 'serial' 
    WHEN sc.paramtype IN (7,263)  THEN 'date' 
    WHEN sc.paramtype IN (8,264)  THEN 'money' 
    WHEN sc.paramtype IN (9,265)  THEN 'null' 
    WHEN sc.paramtype IN (10,266) THEN 'datetime' 
    WHEN sc.paramtype IN (11,267) THEN 'byte' 
    WHEN sc.paramtype IN (12,268) THEN 'text' 
    WHEN sc.paramtype IN (13,269) THEN 'varchar' 
    WHEN sc.paramtype IN (14,270) THEN 'interval' 
    WHEN sc.paramtype IN (15,271) THEN 'nchar' 
    WHEN sc.paramtype IN (16,272) THEN 'nvarchar' 
    WHEN sc.paramtype IN (17,273) THEN 'int8' 
    WHEN sc.paramtype IN (18,274) THEN 'serial8' 
    WHEN sc.paramtype IN (19,275) THEN 'set' 
    WHEN sc.paramtype IN (20,276) THEN 'multiset' 
    WHEN sc.paramtype IN (21,277) THEN 'list' 
    WHEN sc.paramtype IN (22,278) THEN 'row' 
    WHEN sc.paramtype IN (23,279) THEN 'collection' 
    WHEN sc.paramtype IN (43,299) THEN 'lvarchar' 
    WHEN sc.paramtype IN (45,301) THEN 'boolean' 
    WHEN sc.paramtype IN (52,308) THEN 'bigint' 
    WHEN sc.paramtype IN (53,309) THEN 'bigserial' 
    ELSE 
        CASE 
            WHEN (sc.paramxid > 0) THEN 
                (SELECT LOWER(name) FROM sysxtdtypes WHERE 
                    extended_id = sc.paramxid) 
            ELSE 'unknown'
        END 
END typename, 
CASE 
    WHEN (sc.paramtype IN (13,269,16,272)) THEN  
        CASE 
            WHEN (sc.paramlen > 0) THEN MOD(sc.paramlen,256)::INT 
            ELSE MOD(sc.paramlen+65536,256)::INT 
        END 
    ELSE 
        NULL 
END maxlength, 
CASE 
    WHEN (sc.paramtype IN (13,269,16,272)) THEN  
        CASE 
            WHEN (sc.paramlen > 0) THEN (sc.paramlen/256)::INT 
            ELSE ((65536+sc.paramlen)/256)::INT 
        END 
    ELSE 
        NULL 
END minlength, 
CASE 
    WHEN (sc.paramtype IN (5,261,8,264) AND (sc.paramlen / 256) >= 1) 
        THEN (sc.paramlen / 256)::INT  
    ELSE 
        NULL 
END precision, 
CASE 
    WHEN (sc.paramtype IN (5,261,8,264) AND (MOD(sc.paramlen, 256) <> 255)) 
        THEN MOD(sc.paramlen, 256)::INT  
    ELSE 
        NULL 
END scale, 
CASE  
    WHEN (sc.paramtype < 256) THEN 'Y' 
    WHEN (sc.paramtype BETWEEN 256 AND 309) THEN 'N' 
    ELSE 
        NULL 
END nulls, 
CASE 
    WHEN sc.paramattr = 1  THEN 'IN' 
    WHEN sc.paramattr = 2  THEN 'INOUT' 
    WHEN sc.paramattr = 3  THEN 'MR' 
    WHEN sc.paramattr = 4  THEN 'OUT' 
    WHEN sc.paramattr = 5  THEN 'R' 
    ELSE 'unknown'
END paramtype 
FROM SYSPROCCOLUMNS sc 
LEFT OUTER JOIN sysdefaults sd ON (sc.procid = sd.tabid AND sc.paramid = sd.colno AND sd.class = 'P' AND sd.type = 'L') 
WHERE procid = :id;
MYSQL;

        $rows = $this->connection->select($sql, $bindings);
        foreach ($rows as $row) {
            $row = array_change_key_case((array)$row, CASE_UPPER);
            $paramName = array_get($row, 'PARAMNAME');
            $dbType = array_get($row, 'TYPENAME');
            $simpleType = static::extractSimpleType($dbType);
            $pos = intval(array_get($row, 'PARAMID'));
            $length = (isset($row['PARAMLEN']) ? intval(array_get($row, 'PARAMLEN')) : null);
            $precision = (isset($row['PRECISION']) ? intval(array_get($row, 'PRECISION')) : null);
            $scale = (isset($row['SCALE']) ? intval(array_get($row, 'SCALE')) : null);
            switch ($paramType = strtoupper(trim(array_get($row, 'PARAMTYPE', '')))) {
                case 'IN':
                case 'INOUT':
                case 'OUT':
                    $holder->addParameter(new ParameterSchema(
                        [
                            'name'          => $paramName,
                            'position'      => $pos,
                            'param_type'    => $paramType,
                            'type'          => $simpleType,
                            'db_type'       => $dbType,
                            'length'        => $length,
                            'precision'     => $precision,
                            'scale'         => $scale,
                            'default_value' => array_get($row, 'DEFAULT'),
                        ]
                    ));
                    break;
                case 'R':
                    if (empty($holder->returnType)) {
                        $holder->returnType = $simpleType;
                    }
                    break;
                case 'MR':
                    $holder->returnSchema[] = [
                        'name'      => $paramName,
                        'position'  => $pos,
                        'type'      => $simpleType,
                        'db_type'   => $dbType,
                        'length'    => $length,
                        'precision' => $precision,
                        'scale'     => $scale,
                    ];
                    break;
                default:
                    break;
            }
        }
    }

    public static function getNativeDateTimeFormat($field_info)
    {
        $type = DbSimpleTypes::TYPE_STRING;
        if (is_string($field_info)) {
            $type = $field_info;
        } elseif ($field_info instanceof ColumnSchema) {
            $type = $field_info->type;
        } elseif ($field_info instanceof ParameterSchema) {
            $type = $field_info->type;
        }
        switch (strtolower(strval($type))) {
            case DbSimpleTypes::TYPE_DATE:
                return 'Y-m-d';

            case DbSimpleTypes::TYPE_TIME:
                return 'H:i:s.u';
            case DbSimpleTypes::TYPE_TIME_TZ:
                return 'H:i:s.u P';

            case DbSimpleTypes::TYPE_DATETIME:
            case DbSimpleTypes::TYPE_TIMESTAMP:
            case DbSimpleTypes::TYPE_TIMESTAMP_ON_CREATE:
            case DbSimpleTypes::TYPE_TIMESTAMP_ON_UPDATE:
                return 'Y-m-d H:i:s.u';

            case DbSimpleTypes::TYPE_DATETIME_TZ:
            case DbSimpleTypes::TYPE_TIMESTAMP_TZ:
                return 'Y-m-d H:i:s.u P';
        }

        return null;
    }

    public function getTimestampForSet()
    {
        return $this->connection->raw('(CURRENT)');
    }

    public function typecastToNative($value, $field_info, $allow_null = true)
    {
        switch ($field_info->type) {
            case DbSimpleTypes::TYPE_BOOLEAN:
                if (!(is_null($value) && $field_info->allowNull)) {
                    $value = (to_bool($value) ? 't' : 'f');
                }
                break;
            default:
                $value = parent::typecastToNative($value, $field_info, $allow_null);
                break;
        }

        return $value;
    }

    protected function getFunctionStatement(RoutineSchema $routine, array $param_schemas, array &$values)
    {
        $paramStr = $this->getRoutineParamString($param_schemas, $values);

        return "CALL {$routine->quotedName}($paramStr)";
    }
}