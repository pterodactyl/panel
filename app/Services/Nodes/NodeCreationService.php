<?php

namespace Pterodactyl\Services\Nodes;

use Illuminate\Support\Str;
use Pterodactyl\Models\Node;
use Illuminate\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class NodeCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * CreationService constructor.
     *
     * @param \Illuminate\Encryption\Encrypter $encrypter
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(Encrypter $encrypter, NodeRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->encrypter = $encrypter;
    }

    /**
     * Create a new node on the panel.
     *
     * @param array $data
     * @return \Pterodactyl\Models\Node
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        $data['daemon_token'] = $this->encrypter->encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH));
        $data['daemon_token_id'] = Str::random(Node::DAEMON_TOKEN_ID_LENGTH);

        return $this->repository->create($data, true, true);
    }
}
