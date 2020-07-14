<?php

namespace DreamFactory\Core\PubSub\Resources;

use DreamFactory\Core\PubSub\Components\SwaggerDefinitions;
use DreamFactory\Core\PubSub\Jobs\BaseSubscriber;
use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Exceptions\NotImplementedException;
use Cache;
use DB;

class Sub extends BaseRestResource
{
    const RESOURCE_NAME = 'sub';

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

    /**
     * {@inheritdoc}
     */
    protected function handleGET()
    {
        if (config('queue.default') == 'database') {
            $subscription = $this->getSubscriptionPayload();

            if (!empty($subscription)) {
                return $subscription;
            } else {
                throw new NotFoundException('Did not find any subscribed topic(s)/queue(s). Subscription job may not be running.');
            }
        } else {
            throw new NotImplementedException('Viewing subscribed topics/queues is only supported for database queue at this time.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function handleDELETE()
    {
        // Put a terminate flag in the cache to terminate the subscription job.
        Cache::put(BaseSubscriber::TERMINATOR, true, config('df.default_cache_ttl', 18000));

        return ["success" => true];
    }

    /**
     * Checks to if any subscription job currently running or not
     *
     * @return bool
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function isJobRunning()
    {
        $jobs = $this->getSubscriptionJobs();

        foreach ($jobs as $job) {
            if ($job->attempts == 1) {
                return true;
            } elseif ($job->attempts == 0) {
                throw new InternalServerErrorException('Unprocessed job found in the queue. Please make sure queue worker is running');
            }
        }

        return false;
    }

    /**
     * Returns subscription payload data from cache.
     *
     * @return array
     */
    protected function getSubscriptionPayload()
    {
        $jobs = $this->getSubscriptionJobs();
        $out = [];
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $obj = unserialize(array_get($payload, 'data.command'));
            $out[] = [
                'sub'       => $obj->getPayload(),
                'attempted' => $job->attempts
            ];
        }

        return $out;
    }

    /**
     * Returns all subscription jobs.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getSubscriptionJobs()
    {
        $type = strtoupper($this->parent->getQueueType());
        $jobs = DB::table('jobs')
            ->where('payload', 'like', "%Subscribe%")
            ->where('payload', 'like', "%$type%")
            ->where('payload', 'like', "%DreamFactory%")
            ->get(['id', 'attempts', 'payload']);

        return $jobs;
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
                'get'    => [
                    'summary'     => 'Retrieves subscribed topic(s)',
                    'description' => 'Retrieves subscribed topic(s)',
                    'operationId' => 'get' . $capitalized . 'SubscriptionTopics',
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                            'content'     => [
                                'application/json' => [
                                    'schema' => [
                                        'type'  => 'array',
                                        'items' => [
                                            'type'       => 'object',
                                            'required'   => ['sub', 'attempted'],
                                            'properties' => [
                                                'sub'       => [
                                                    'type'  => 'array',
                                                    'items' => [
                                                        'type'       => 'object',
                                                        'required'   => ['topic', 'service'],
                                                        'properties' => [
                                                            'topic'   => ['type' => 'string'],
                                                            'service' => SwaggerDefinitions::getServiceDef(),
                                                        ]
                                                    ]
                                                ],
                                                'attempted' => ['type' => 'integer'],
                                            ],
                                        ],

                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
                'post'   => [
                    'summary'     => 'Subscribes to topic(s)',
                    'description' => 'Subscribes to topic(s)',
                    'operationId' => 'subscribeTo' . $capitalized . 'Topics',
                    'requestBody' => [
                        'description' => 'Subscription details',
                        'content'     => [
                            'application/json' => [
                                'schema' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type'       => 'object',
                                        'required'   => ['topic', 'service'],
                                        'properties' => [
                                            'topic'   => ['type' => 'string'],
                                            'service' => SwaggerDefinitions::getServiceDef(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'required'    => true
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/Success']
                    ],
                ],
                'delete' => [
                    'summary'     => 'Terminate subscription(s)',
                    'description' => 'Terminate subscription(s)',
                    'operationId' => 'terminatesSubscriptionsTo' . $capitalized,
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/Success']
                    ],
                ]
            ]
        ];

        return $base;
    }
}