<?php

namespace DreamFactory\Core\PubSub\Jobs;

use DreamFactory\Core\PubSub\Contracts\MessageQueueInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\MaxAttemptsExceededException;
use Cache;
use Log;

abstract class BaseSubscriber implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Topic for subscription terminator */
    const TERMINATOR = 'DF:QUEUE:TERMINATE';

    const SUBSCRIPTION = 'DF:QUEUE:SUBSCRIPTION';

    /** @var \DreamFactory\Core\PubSub\Contracts\MessageQueueInterface */
    protected $client;

    /** @var array subscription payload */
    protected $payload;

    /** @var int job retry count */
    public $tries = 1;

    /** @var int job timeout */
    public $timeout = 0;

    /**
     * Validate payload data
     *
     * @param array $payload
     *
     * @return mixed
     */
    abstract public static function validatePayload(array $payload);

    /**
     * Job handler
     */
    abstract public function handle();

    /**
     * Subscribe constructor.
     *
     * @param MessageQueueInterface $client
     * @param array                 $payload
     *
     * @throws \Exception
     */
    public function __construct(MessageQueueInterface $client, $payload)
    {
        $this->client = $client;
        if (!$this->validatePayload($payload)) {
            throw new \Exception('Invalid payload supplied for subscription job.');
        }
        $this->payload = $payload;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param \Exception $exception
     */
    public function failed(\Exception $exception)
    {
        if (!$exception instanceof MaxAttemptsExceededException) {
            Log::debug("[JOB_QUEUE] Job failed. " . $exception->getMessage());
        } else {
            Log::debug("[JOB_QUEUE] Job terminating after attempting maximum amount of time.");
        }
        Cache::forever(static::TERMINATOR, false);
    }
}