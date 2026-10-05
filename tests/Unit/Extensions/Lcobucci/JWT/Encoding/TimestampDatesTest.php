<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Extensions\Lcobucci\JWT\Encoding\TimestampDatesTest;

use DateTimeImmutable;
use Pterodactyl\Extensions\Lcobucci\JWT\Encoding\TimestampDates;
use UnexpectedValueException;

test('formats registered date claims as unix timestamps', function () {
    $claims = (new TimestampDates())->formatClaims(['iat' => new DateTimeImmutable('@1700000000'), 'server' => ['uuid' => 'example']]);
    expect($claims['iat'])->toBe(1700000000);
    expect($claims['server'])->toBe(['uuid' => 'example']);
});
test('rejects non date registered claims', function () {
    $this->expectException(UnexpectedValueException::class);
    (new TimestampDates())->formatClaims(['exp' => 'tomorrow']);
});
