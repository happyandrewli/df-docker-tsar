<?php

namespace DreamFactory\Core\PubSub\Contracts;

interface MessageQueueInterface
{
    public function subscribe(array $payload);

    public function publish(array $data);
}