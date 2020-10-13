<?php

namespace Tests\Unit\Services\Nodes;

use Mockery as m;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\Node;
use Ramsey\Uuid\UuidFactory;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class NodeCreationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Mockery\MockInterface
     */
    private $repository;

    /**
     * @var \Mockery\MockInterface
     */
    private $encrypter;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        /* @noinspection PhpParamsInspection */
        Uuid::setFactory(
            m::mock(UuidFactory::class . '[uuid4]', [
                'uuid4' => Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            ])
        );

        $this->repository = m::mock(NodeRepositoryInterface::class);
        $this->encrypter = m::mock(Encrypter::class);
    }

    /**
     * Test that a node is created and a daemon secret token is created.
     */
    public function testNodeIsCreatedAndDaemonSecretIsGenerated()
    {
        /** @var \Pterodactyl\Models\Node $node */
        $node = factory(Node::class)->make();

        $this->encrypter->expects('encrypt')->with(m::on(function ($value) {
            return strlen($value) === Node::DAEMON_TOKEN_LENGTH;
        }))->andReturns('encrypted_value');

        $this->repository->expects('create')->with(m::on(function ($value) {
            $this->assertTrue(is_array($value));
            $this->assertSame('NodeName', $value['name']);
            $this->assertSame('00000000-0000-0000-0000-000000000000', $value['uuid']);
            $this->assertSame('encrypted_value', $value['daemon_token']);
            $this->assertTrue(strlen($value['daemon_token_id']) === Node::DAEMON_TOKEN_ID_LENGTH);

            return true;
        }), true, true)->andReturn($node);

        $this->assertSame($node, $this->getService()->handle(['name' => 'NodeName']));
    }

    /**
     * @return \Pterodactyl\Services\Nodes\NodeCreationService
     */
    private function getService()
    {
        return new NodeCreationService($this->encrypter, $this->repository);
    }
}
