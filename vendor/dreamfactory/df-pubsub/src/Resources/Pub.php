<?php

namespace DreamFactory\Core\PubSub\Resources;

use DreamFactory\Core\Resources\BaseRestResource;

class Pub extends BaseRestResource
{
    const RESOURCE_NAME = 'pub';

    /** A resource identifier used in swagger doc. */
    const RESOURCE_IDENTIFIER = 'name';

    /** @var \DreamFactory\Core\PubSub\Services\PubSub */
    protected $parent;

    /**
     * {@inheritdoc}
     */
    protected static function getResourceIdentifier()
    {
        return static::RESOURCE_IDENTIFIER;
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $service = $this->getServiceName();
        $capitalized = camelize($service);
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $base = [
            $path => [
                'post' => [
                    'summary'     => 'Publish message',
                    'description' => 'Publishes message to MQTT broker',
                    'operationId' => 'publish' . $capitalized . 'Message',
                    'requestBody' => [
                        'description' => 'Content - Message and topic to publish to',
                        'content'     => [
                            'application/json' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'required'   => ['topic', 'message'],
                                    'properties' => [
                                        'topic'   => [
                                            'type'        => 'string',
                                            'description' => 'Topic name'
                                        ],
                                        'message' => [
                                            'type'        => 'string',
                                            'description' => 'Payload message'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/Success']
                    ],
                ],
            ],
        ];

        return $base;
    }
}