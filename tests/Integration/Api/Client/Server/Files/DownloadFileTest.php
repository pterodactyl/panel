<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Files;

use Lcobucci\JWT\Configuration;
use Pterodactyl\Models\Permission;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class DownloadFileTest extends ClientApiIntegrationTestCase
{
    public function testFilePathIsPassedToWingsAsRequested(): void
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_FILE_READ_CONTENT]);

        $file = '/mods/modernfix-forge-5.19.6%2Bmc1.20.1.jar';

        $url = $this->actingAs($user)
            ->getJson($this->link($server, '/files/download?file=' . rawurlencode($file)))
            ->assertOk()
            ->json('attributes.url');

        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        $token = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($server->node->getDecryptedKey()))
            ->parser()
            ->parse($query['token']);

        $this->assertSame($file, $token->claims()->get('file_path'));
    }
}
