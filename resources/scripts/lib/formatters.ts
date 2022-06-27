const _CONVERSION_UNIT = 1000;

/**
 * Given a value in megabytes converts it back down into bytes.
 */
function mbToBytes(megabytes: number): number {
    return Math.floor(megabytes * _CONVERSION_UNIT * _CONVERSION_UNIT);
}

/**
 * Given an amount of bytes, converts them into a human readable string format
 * using "1000" as the divisor.
 */
function bytesToString(bytes: number): string {
    if (bytes < 1) return '0 Bytes';

    const i = Math.floor(Math.log(bytes) / Math.log(_CONVERSION_UNIT));
    const value = Number((bytes / Math.pow(_CONVERSION_UNIT, i)).toFixed(2));

    return `${value} ${['Bytes', 'KB', 'MB', 'GB', 'TB'][i]}`;
}

/**
 * Formats an IPv4 or IPv6 address.
 */
function ip(value: string): string {
    // noinspection RegExpSimplifiable
    return /([a-f0-9:]+:+)+[a-f0-9]+/.test(value) ? `[${value}]` : value;
}

export { ip, mbToBytes, bytesToString };
