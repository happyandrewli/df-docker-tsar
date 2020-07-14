<?php

namespace DreamFactory\Core\MemSql\Services;

use DreamFactory\Core\MySqlDb\Services\MySqlDb;

/**
 * Class MemSqlDb
 *
 * @package DreamFactory\Core\MySqlDb\Services
 */
class MemSqlDb extends MySqlDb
{
    public static function getDriverName()
    {
        return 'memsql';
    }
}