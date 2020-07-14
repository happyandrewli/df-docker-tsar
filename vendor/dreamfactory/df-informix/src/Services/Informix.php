<?php

namespace DreamFactory\Core\Informix\Services;

use DreamFactory\Core\SqlDb\Resources\StoredFunction;
use DreamFactory\Core\SqlDb\Resources\StoredProcedure;
use DreamFactory\Core\SqlDb\Services\SqlDb;

/**
 * Class Informix
 *
 * @package DreamFactory\Core\SqlDb\Services
 */
class Informix extends SqlDb
{
    public function __construct($settings = [])
    {
        parent::__construct($settings);

        $prefix = parent::getConfigBasedCachePrefix();
        if ($service = array_get($this->config, 'service')) {
            $prefix = $service . $prefix;
        }
        if ($server = array_get($this->config, 'server')) {
            $prefix = $server . $prefix;
        }
        $this->setConfigBasedCachePrefix($prefix);
    }

    public static function adaptConfig(array &$config)
    {
        $config['driver'] = 'informix';
        parent::adaptConfig($config);
    }

    public function getResourceHandlers()
    {
        $handlers = parent::getResourceHandlers();

        $handlers[StoredProcedure::RESOURCE_NAME] = [
            'name'       => StoredProcedure::RESOURCE_NAME,
            'class_name' => StoredProcedure::class,
            'label'      => 'Stored Procedure',
        ];
        $handlers[StoredFunction::RESOURCE_NAME] = [
            'name'       => StoredFunction::RESOURCE_NAME,
            'class_name' => StoredFunction::class,
            'label'      => 'Stored Function',
        ];

        return $handlers;
    }
}