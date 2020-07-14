<?php

namespace DreamFactory\Core\MemSql\Database\Schema;

use DreamFactory\Core\Enums\DbSimpleTypes;
use DreamFactory\Core\SqlDb\Database\Schema\MySqlSchema;

/**
 * Schema is the class for retrieving metadata information from a MemSQL database.
 */
class MemSqlSchema extends MySqlSchema
{
    protected function translateSimpleColumnTypes(array &$info)
    {
        // override this in each schema class
        $type = (isset($info['type'])) ? $info['type'] : null;
        switch ($type) {
            // some types need massaging, some need other required properties
            case 'pk':
            case DbSimpleTypes::TYPE_ID:
                $info['type'] = 'int';
                $info['type_extras'] = '(11)';
                $info['allow_null'] = false;
                $info['auto_increment'] = true;
                $info['is_primary_key'] = true;
                break;

            case 'fk':
            case DbSimpleTypes::TYPE_REF:
                $info['type'] = 'int';
                $info['type_extras'] = '(11)';
                $info['is_foreign_key'] = true;
                // check foreign tables
                break;

            case DbSimpleTypes::TYPE_TIMESTAMP_ON_CREATE:
            case DbSimpleTypes::TYPE_TIMESTAMP_ON_UPDATE:
                $info['type'] = 'timestamp';
                $default = (isset($info['default'])) ? $info['default'] : null;
                if (!isset($default)) {
                    $default = 'CURRENT_TIMESTAMP';
                    if (DbSimpleTypes::TYPE_TIMESTAMP_ON_UPDATE === $type) {
                        $default .= ' ON UPDATE CURRENT_TIMESTAMP';
                    }
                    $info['default'] = ['expression' => $default];
                }
                break;

            case DbSimpleTypes::TYPE_USER_ID:
            case DbSimpleTypes::TYPE_USER_ID_ON_CREATE:
            case DbSimpleTypes::TYPE_USER_ID_ON_UPDATE:
                $info['type'] = 'int';
                $info['type_extras'] = '(11)';
                break;

            case DbSimpleTypes::TYPE_BOOLEAN:
                $info['type'] = 'tinyint';
                $info['type_extras'] = '(1)';
                $default = (isset($info['default'])) ? $info['default'] : null;
                if (isset($default)) {
                    // convert to bit 0 or 1, where necessary
                    $info['default'] = (int)filter_var($default, FILTER_VALIDATE_BOOLEAN);
                }
                break;

            case DbSimpleTypes::TYPE_MONEY:
                $info['type'] = 'decimal';
                $info['type_extras'] = '(19,4)';
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

            case DbSimpleTypes::TYPE_BINARY:
                $fixed =
                    (isset($info['fixed_length'])) ? filter_var($info['fixed_length'], FILTER_VALIDATE_BOOLEAN) : false;
                $info['type'] = ($fixed) ? 'binary' : 'varbinary';
                break;
        }
    }

    public function getSchemas()
    {
        $sql = <<<MYSQL
SHOW DATABASES
MYSQL;

        $results = $this->selectColumn($sql);
        $results = array_values(array_flip(array_except(array_flip($results), ['information_schema', 'memsql', 'cluster'])));

        return $results;
    }
}
