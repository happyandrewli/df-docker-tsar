<?php

namespace DreamFactory\Core\Informix\Models;

use DreamFactory\Core\SqlDb\Models\SqlDbConfig;

/**
 * InformixDbConfig
 *
 */
class InformixDbConfig extends SqlDbConfig
{
    public static function getDriverName()
    {
        return 'informix';
    }

    public static function getDefaultPort()
    {
        return "see the host's /etc/services file for the correct value";
    }

    protected function getConnectionFields()
    {
        $fields = parent::getConnectionFields();

        return array_merge($fields, ['service', 'server', 'protocol']);
    }

    public static function getDefaultConnectionInfo()
    {
        $defaults = parent::getDefaultConnectionInfo();
        $defaults[] = [
            'name'        => 'server',
            'label'       => 'Server Name',
            'type'        => 'string',
            'description' => 'Specifies the name of the IBM Informix database server. ' .
                'This value corresponds to the server name (first column) in the /etc/sqlhosts file.'
        ];
        $defaults[] = [
            'name'        => 'protocol',
            'label'       => 'Connection Protocol',
            'type'        => 'string',
            'description' => 'The protocol to use for this connection. ' .
                'This value corresponds to the protocol (second column) of the /etc/sqlhosts entry.',
            'default'     => 'onsoctcp'
        ];
        $defaults[] = [
            'name'        => 'service',
            'label'       => 'Service Name',
            'type'        => 'string',
            'description' => 'Specifies the service name if the TCP port is not given. '.
                'This value corresponds to the service/port (fourth column) in the /etc/sqlhosts file, '.
                'which must also be defined in the /etc/services file, used by the server to accept incoming connections.'
        ];

        return $defaults;
    }
}