<?php

namespace DreamFactory\Core\MemSql\Database\Connectors;

use Illuminate\Database\Connectors\MySqlConnector;
use PDO;

class MemSqlConnector extends MySqlConnector
{
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => true,
    ];
}
