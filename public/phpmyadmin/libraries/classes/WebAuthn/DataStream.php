<?php

declare(strict_types=1);

namespace PhpMyAdmin\WebAuthn;

use function fopen;
use function fread;
use function ftell;
use function fwrite;
use function rewind;

final class DataStream
{
    /** @var resource */
    private $stream;

    /**
     * @throws WebAuthnException
     */
    public function __construct(string $binaryString)
    {
        $resource = fopen('php://memory', 'rb+');
        if ($resource === false || fwrite($resource, $binaryString) === false) {
            throw new WebAuthnException();
        }

        if (! rewind($resource)) {
            throw new WebAuthnException();
        }

        $this->stream = $resource;
    }

    /**
     * @throws WebAuthnException
     */
    public function take(int $length): string
    {
        if ($length < 0) {
            throw new WebAuthnException();
        }

        if ($length === 0) {
            return '';
        }

        $string = fread($this->stream, $length);
        if ($string === false) {
            throw new WebAuthnException();
        }

        return $string;
    }

    /**
     * @throws WebAuthnException
     */
    public function getPosition(): int
    {
        $position = ftell($this->stream);
        if ($position === false) {
            throw new WebAuthnException();
        }

        return $position;
    }
}
