<?php

namespace DreamFactory\Core\MemSql;

use DreamFactory\Core\Components\DbSchemaExtensions;
use DreamFactory\Core\Enums\LicenseLevel;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\MemSql\Database\Connectors\MemSqlConnector;
use DreamFactory\Core\MemSql\Database\Schema\MemSqlSchema;
use DreamFactory\Core\MemSql\Database\MemSqlConnection;
use DreamFactory\Core\MemSql\Models\MemSqlDbConfig;
use DreamFactory\Core\MemSql\Services\MemSqlDb;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use Illuminate\Database\DatabaseManager;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our database drivers override.
        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('memsql', function ($config) {
                $connector = new MemSqlConnector();
                $connection = $connector->connect($config);

                return new MemSqlConnection($connection, $config["database"], $config["prefix"], $config);
            });
        });

        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'                  => 'memsql',
                    'label'                 => 'MemSQL',
                    'description'           => 'Database service supporting MemSQL connections.',
                    'group'                 => ServiceTypeGroups::DATABASE,
                    'subscription_required' => LicenseLevel::SILVER,
                    'config_handler'        => MemSqlDbConfig::class,
                    'factory'               => function ($config) {
                        return new MemSqlDb($config);
                    },
                ])
            );
        });

        // Add our database extensions.
        $this->app->resolving('db.schema', function (DbSchemaExtensions $db) {
            $db->extend('memsql', function ($connection) {
                return new MemSqlSchema($connection);
            });
        });
    }
}
