<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Wings;

use GuzzleHttp\Client;
use Webmozart\Assert\Assert;
use Illuminate\Foundation\Application;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\Daemon\BaseRepositoryInterface;

class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var
     */
    protected $accessServer;

    /**
     * @var
     */
    protected $accessToken;

    /**
     * @var
     */
    protected $node;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * BaseRepository constructor.
     *
     * @param \Illuminate\Foundation\Application                        $app
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $nodeRepository
     */
    public function __construct(
        Application $app,
        ConfigRepository $config,
        NodeRepositoryInterface $nodeRepository
    ) {
        $this->app = $app;
        $this->config = $config;
        $this->nodeRepository = $nodeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function setNode($id)
    {
        Assert::numeric($id, 'The first argument passed to setNode must be numeric, received %s.');

        $this->node = $this->nodeRepository->find($id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessServer($server = null)
    {
        Assert::nullOrString($server, 'The first argument passed to setAccessServer must be null or a string, received %s.');

        $this->accessServer = $server;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessServer()
    {
        return $this->accessServer;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessToken($token = null)
    {
        Assert::nullOrString($token, 'The first argument passed to setAccessToken must be null or a string, received %s.');

        $this->accessToken = $token;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpClient(array $headers = [])
    {
        if (! is_null($this->accessToken)) {
            $headers['Authorization'] = 'Bearer ' . $this->getAccessToken();
        } elseif (! is_null($this->node)) {
            $headers['Authorization'] = 'Bearer ' . $this->getNode()->daemonSecret;
        }

        return new Client([
            'base_uri' => sprintf('%s://%s:%s/v1/', $this->getNode()->scheme, $this->getNode()->fqdn, $this->getNode()->daemonListen),
            'timeout' => $this->config->get('pterodactyl.guzzle.timeout'),
            'connect_timeout' => $this->config->get('pterodactyl.guzzle.connect_timeout'),
            'headers' => $headers,
        ]);
    }
}
