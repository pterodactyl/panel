<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR;

use Brick\Math\BigInteger;
use GMP;
use InvalidArgumentException;

final class SignedIntegerObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = 0b001;

    /**
     * @var string|null
     */
    private $data;

    public function __construct(int $additionalInformation, ?string $data)
    {
        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
        $this->data = $data;
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        if (null !== $this->data) {
            $result .= $this->data;
        }

        return $result;
    }

    public static function createObjectForValue(int $additionalInformation, ?string $data): self
    {
        return new self($additionalInformation, $data);
    }

    public static function create(int $value): self
    {
        return self::createFromString((string) $value);
    }

    public static function createFromString(string $value): self
    {
        $integer = BigInteger::of($value);

        return self::createBigInteger($integer);
    }

    /**
     * @deprecated Deprecated since v1.1 and will be removed in v2.0. Please use "create" or "createFromString" instead
     */
    public static function createFromGmpValue(GMP $value): self
    {
        if (gmp_cmp($value, gmp_init(0)) >= 0) {
            throw new InvalidArgumentException('The value must be a negative integer.');
        }

        $minusOne = gmp_init(-1);
        $computed_value = gmp_sub($minusOne, $value);

        switch (true) {
            case gmp_intval($computed_value) < 24:
                $ai = gmp_intval($computed_value);
                $data = null;
                break;
            case gmp_cmp($computed_value, gmp_init('FF', 16)) < 0:
                $ai = 24;
                $data = self::hex2bin(str_pad(gmp_strval($computed_value, 16), 2, '0', STR_PAD_LEFT));
                break;
            case gmp_cmp($computed_value, gmp_init('FFFF', 16)) < 0:
                $ai = 25;
                $data = self::hex2bin(str_pad(gmp_strval($computed_value, 16), 4, '0', STR_PAD_LEFT));
                break;
            case gmp_cmp($computed_value, gmp_init('FFFFFFFF', 16)) < 0:
                $ai = 26;
                $data = self::hex2bin(str_pad(gmp_strval($computed_value, 16), 8, '0', STR_PAD_LEFT));
                break;
            default:
                throw new InvalidArgumentException('Out of range. Please use NegativeBigIntegerTag tag with ByteStringObject object instead.');
        }

        return new self($ai, $data);
    }

    public function getValue(): string
    {
        return $this->getNormalizedData();
    }

    public function getNormalizedData(bool $ignoreTags = false): string
    {
        if (null === $this->data) {
            return (string) (-1 - $this->additionalInformation);
        }

        $result = Utils::binToBigInteger($this->data);
        $minusOne = BigInteger::of(-1);

        return $minusOne->minus($result)->toBase(10);
    }

    private static function createBigInteger(BigInteger $integer): self
    {
        if ($integer->isGreaterThanOrEqualTo(BigInteger::zero())) {
            throw new InvalidArgumentException('The value must be a negative integer.');
        }

        $minusOne = BigInteger::of(-1);
        $computed_value = $minusOne->minus($integer);

        switch (true) {
            case $computed_value->isLessThan(BigInteger::of(24)):
                $ai = $computed_value->toInt();
                $data = null;
                break;
            case $computed_value->isLessThan(BigInteger::fromBase('FF', 16)):
                $ai = 24;
                $data = self::hex2bin(str_pad($computed_value->toBase(16), 2, '0', STR_PAD_LEFT));
                break;
            case $computed_value->isLessThan(BigInteger::fromBase('FFFF', 16)):
                $ai = 25;
                $data = self::hex2bin(str_pad($computed_value->toBase(16), 4, '0', STR_PAD_LEFT));
                break;
            case $computed_value->isLessThan(BigInteger::fromBase('FFFFFFFF', 16)):
                $ai = 26;
                $data = self::hex2bin(str_pad($computed_value->toBase(16), 8, '0', STR_PAD_LEFT));
                break;
            default:
                throw new InvalidArgumentException('Out of range. Please use NegativeBigIntegerTag tag with ByteStringObject object instead.');
        }

        return new self($ai, $data);
    }

    private static function hex2bin(string $data): string
    {
        $result = hex2bin($data);
        if (false === $result) {
            throw new InvalidArgumentException('Unable to convert the data');
        }

        return $result;
    }
}
