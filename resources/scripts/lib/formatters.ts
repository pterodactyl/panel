const _CONVERSION_UNIT = 1024;

function mbToBytes(megabytes: number): number {
    return Math.floor(megabytes * _CONVERSION_UNIT * _CONVERSION_UNIT);
}

const _BYTE_UNITS = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB'];

function byteUnitIndex(bytes: number): number {
    return Math.min(Math.floor(Math.log(bytes) / Math.log(_CONVERSION_UNIT)), _BYTE_UNITS.length - 1);
}

function scaleBytes(bytes: number, unit: number, decimals: number): number {
    return Number((bytes / _CONVERSION_UNIT ** unit).toFixed(Math.floor(Math.max(0, decimals))));
}

function bytesToString(bytes: number, decimals = 2): string {
    if (bytes < 1) {
        return '0 Bytes';
    }

    const i = byteUnitIndex(bytes);

    return `${scaleBytes(bytes, i, decimals)} ${_BYTE_UNITS[i]}`;
}

function bytesRatioToString(used: number, total: number, decimals = 2): string {
    const reference = Math.max(total, used);

    if (reference < 1) {
        return '0 / 0 Bytes';
    }

    const i = byteUnitIndex(reference);

    return `${scaleBytes(Math.max(0, used), i, decimals)} / ${scaleBytes(total, i, decimals)} ${_BYTE_UNITS[i]}`;
}

/** Wraps IPv6 addresses in brackets. */
function ip(value: string): string {
    // noinspection RegExpSimplifiable
    return /([a-f0-9:]+:+)+[a-f0-9]+/.test(value) ? `[${value}]` : value;
}

export { ip, mbToBytes, bytesToString, bytesRatioToString };
