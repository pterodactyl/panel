<?php

declare(strict_types=1);

namespace PhpMyAdmin\WebAuthn;

use Webmozart\Assert\Assert;

use function ord;
use function unpack;

use const INF;
use const NAN;

/**
 * Concise Binary Object Representation (CBOR) decoder.
 *
 * This is not a general purpose CBOR decoder and only implements the CTAP2 canonical CBOR encoding form.
 *
 * @see https://www.rfc-editor.org/rfc/rfc7049
 * @see https://fidoalliance.org/specs/fido-v2.0-ps-20190130/fido-client-to-authenticator-protocol-v2.0-ps-20190130.html#message-encoding
 */
final class CBORDecoder
{
    /**
     * @return mixed
     *
     * @throws WebAuthnException
     */
    public function decode(DataStream $stream)
    {
        return $this->wellFormed($stream);
    }

    /**
     * @see https://www.rfc-editor.org/rfc/rfc7049#appendix-C
     *
     * @return mixed
     *
     * @throws WebAuthnException
     */
    private function wellFormed(DataStream $stream)
    {
        // process initial bytes
        $initialByte = ord($stream->take(1));
        $majorType = $initialByte >> 5;
        $value = $additionalInformation = $initialByte & 0x1f;
        switch ($additionalInformation) {
            case 24:
                if ($majorType !== 7) {
                    $value = ord($stream->take(1));
                }

                break;
            case 25:
                if ($majorType !== 7) {
                    $unpackedValue = unpack('n', $stream->take(2));
                    Assert::isArray($unpackedValue);
                    Assert::keyExists($unpackedValue, 1);
                    Assert::integer($unpackedValue[1]);
                    $value = $unpackedValue[1];
                }

                break;
            case 26:
                if ($majorType !== 7) {
                    $unpackedValue = unpack('N', $stream->take(4));
                    Assert::isArray($unpackedValue);
                    Assert::keyExists($unpackedValue, 1);
                    Assert::integer($unpackedValue[1]);
                    $value = $unpackedValue[1];
                }

                break;
            case 27:
                if ($majorType !== 7) {
                    $unpackedValue = unpack('J', $stream->take(8));
                    Assert::isArray($unpackedValue);
                    Assert::keyExists($unpackedValue, 1);
                    Assert::integer($unpackedValue[1]);
                    $value = $unpackedValue[1];
                }

                break;
            case 28:
            case 29:
            case 30:
            case 31:
                throw new WebAuthnException();
        }

        // process content
        switch ($majorType) {
            case 0:
                return $this->getUnsignedInteger($value);

            case 1:
                return $this->getNegativeInteger($value);

            case 2:
                return $this->getByteString($stream, $value);

            case 3:
                return $this->getTextString($stream, $value);

            case 4:
                return $this->getList($stream, $value);

            case 5:
                return $this->getMap($stream, $value);

            case 6:
                return $this->getTag($stream);

            case 7:
                return $this->getFloatNumberOrSimpleValue($stream, $value, $additionalInformation);

            default:
                throw new WebAuthnException();
        }
    }

    private function getUnsignedInteger(int $value): int
    {
        return $value;
    }

    private function getNegativeInteger(int $value): int
    {
        return -1 - $value;
    }

    /**
     * @throws WebAuthnException
     */
    private function getByteString(DataStream $stream, int $value): string
    {
        return $stream->take($value);
    }

    /**
     * @throws WebAuthnException
     */
    private function getTextString(DataStream $stream, int $value): string
    {
        return $stream->take($value);
    }

    /**
     * @psalm-return list<mixed>
     *
     * @throws WebAuthnException
     */
    private function getList(DataStream $stream, int $value): array
    {
        $list = [];
        for ($i = 0; $i < $value; $i++) {
            /** @psalm-suppress MixedAssignment */
            $list[] = $this->wellFormed($stream);
        }

        return $list;
    }

    /**
     * @psalm-return array<array-key, mixed>
     *
     * @throws WebAuthnException
     */
    private function getMap(DataStream $stream, int $value): array
    {
        $map = [];
        for ($i = 0; $i < $value; $i++) {
            /** @psalm-suppress MixedAssignment, MixedArrayOffset */
            $map[$this->wellFormed($stream)] = $this->wellFormed($stream);
        }

        return $map;
    }

    /**
     * @return mixed
     *
     * @throws WebAuthnException
     */
    private function getTag(DataStream $stream)
    {
        // 1 embedded data item
        return $this->wellFormed($stream);
    }

    /**
     * @return mixed
     *
     * @throws WebAuthnException
     */
    private function getFloatNumberOrSimpleValue(DataStream $stream, int $value, int $additionalInformation)
    {
        switch ($additionalInformation) {
            case 20:
                return true;

            case 21:
                return false;

            case 22:
                return null;

            case 24:
                // simple value
                return ord($stream->take(1));

            case 25:
                return $this->getHalfFloat($stream);

            case 26:
                return $this->getSingleFloat($stream);

            case 27:
                return $this->getDoubleFloat($stream);

            case 31:
                // "break" stop code for indefinite-length items
                throw new WebAuthnException();

            default:
                return $value;
        }
    }

    /**
     * IEEE 754 Half-Precision Float (16 bits follow)
     *
     * @see https://www.rfc-editor.org/rfc/rfc7049#appendix-D
     *
     * @throws WebAuthnException
     */
    private function getHalfFloat(DataStream $stream): float
    {
        $value = unpack('n', $stream->take(2));
        Assert::isArray($value);
        Assert::keyExists($value, 1);
        Assert::integer($value[1]);

        $half = $value[1];
        $exp = ($half >> 10) & 0x1f;
        $mant = $half & 0x3ff;

        if ($exp === 0) {
            $val = $mant * (2 ** -24);
        } elseif ($exp !== 31) {
            $val = ($mant + 1024) * (2 ** ($exp - 25));
        } else {
            $val = $mant === 0 ? INF : NAN;
        }

        return $half & 0x8000 ? -$val : $val;
    }

    /**
     * IEEE 754 Single-Precision Float (32 bits follow)
     *
     * @throws WebAuthnException
     */
    private function getSingleFloat(DataStream $stream): float
    {
        $value = unpack('G', $stream->take(4));
        Assert::isArray($value);
        Assert::keyExists($value, 1);
        Assert::float($value[1]);

        return $value[1];
    }

    /**
     * IEEE 754 Double-Precision Float (64 bits follow)
     *
     * @throws WebAuthnException
     */
    private function getDoubleFloat(DataStream $stream): float
    {
        $value = unpack('E', $stream->take(8));
        Assert::isArray($value);
        Assert::keyExists($value, 1);
        Assert::float($value[1]);

        return $value[1];
    }
}
