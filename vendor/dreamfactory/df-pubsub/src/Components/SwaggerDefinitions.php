<?php

namespace DreamFactory\Core\PubSub\Components;

class SwaggerDefinitions
{
    /**
     * @return array
     */
    static public function getServiceDef()
    {
        return [
            'type'       => 'object',
            'required'   => ['endpoint'],
            'properties' => [
                'endpoint'  => [
                    'type'        => 'string',
                    'description' => 'Internal DreamFactory Endpoint. Ex: system/role'
                ],
                'verb'      => [
                    'type'        => 'string',
                    'default'     => 'POST',
                    'description' => 'GET, POST, PATCH, PUT, DELETE'
                ],
                'parameter' => [
                    'type'        => 'array',
                    'items'       => ['type' => 'object'],
                    'description' => 'Enter any request parameter(s) for your endpoint as needed.'
                ],
                'header'    => [
                    'type'        => 'array',
                    'items'       => ['type' => 'object'],
                    'description' => 'Enter any request header(s) for your endpoint as needed.'
                ],
                'payload'   => [
                    'type'        => 'array',
                    'items'       => ['type' => 'object'],
                    'description' => 'Enter any request payload for your endpoint as needed. However, do not use key \'message\' as it will be overwritten by the message/payload received in the subscriber/consumer job.'
                ]
            ]
        ];
    }
}