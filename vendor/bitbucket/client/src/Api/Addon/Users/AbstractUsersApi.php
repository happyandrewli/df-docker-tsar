<?php

declare(strict_types=1);

/*
 * This file is part of Bitbucket API Client.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bitbucket\Api\Addon\Users;

use Bitbucket\Api\Addon\AbstractAddonApi;
use Http\Client\Common\HttpMethodsClient;

/**
 * The abstract users api class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
abstract class AbstractUsersApi extends AbstractAddonApi
{
    /**
     * The username.
     *
     * @var string
     */
    protected $username;

    /**
     * Create a new api instance.
     *
     * @param \Http\Client\Common\HttpMethodsClient $client
     * @param string                                $username
     */
    public function __construct(HttpMethodsClient $client, string $username)
    {
        parent::__construct($client);
        $this->username = $username;
    }
}
