const _CONVERSION_UNIT = 1024;

function mbToBytes(megabytes: number): number {
    return Math.floor(megabytes * _CONVERSION_UNIT * _CONVERSION_UNIT);
}

function bytesToString(bytes: number, decimals = 2): string {
    const k = _CONVERSION_UNIT;

    if (bytes < 1) {
        return '0 Bytes';
    }

    decimals = Math.floor(Math.max(0, decimals));
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    const value = Number((bytes / k ** i).toFixed(decimals));

    return `${value} ${['Bytes', 'KiB', 'MiB', 'GiB', 'TiB'][i]}`;
}

/** Wraps IPv6 addresses in brackets. */
function ip(value: string): string {
    // noinspection RegExpSimplifiable
    return /([a-f0-9:]+:+)+[a-f0-9]+/.test(value) ? `[${value}]` : value;
}

export { ip, mbToBytes, bytesToString };
