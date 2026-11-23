import i18next from 'i18next';

const _CONVERSION_UNIT = 1024;

function mbToBytes(megabytes: number): number {
    return Math.floor(megabytes * _CONVERSION_UNIT * _CONVERSION_UNIT);
}

const _BYTE_UNITS = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB'];

const unitLabel = (index: number): string => (index === 0 ? i18next.t('Bytes') : _BYTE_UNITS[index]!);

const formatNumber = (value: number): string =>
    new Intl.NumberFormat(i18next.language || 'en', { maximumFractionDigits: 20, useGrouping: false }).format(value);

function byteUnitIndex(bytes: number): number {
    return Math.min(Math.floor(Math.log(bytes) / Math.log(_CONVERSION_UNIT)), _BYTE_UNITS.length - 1);
}

function scaleBytes(bytes: number, unit: number, decimals: number): number {
    return Number((bytes / _CONVERSION_UNIT ** unit).toFixed(Math.floor(Math.max(0, decimals))));
}

function bytesToString(bytes: number, decimals = 2): string {
    if (bytes < 1) {
        return `${formatNumber(0)} ${unitLabel(0)}`;
    }

    const i = byteUnitIndex(bytes);

    return `${formatNumber(scaleBytes(bytes, i, decimals))} ${unitLabel(i)}`;
}

function bytesRatioToString(used: number, total: number, decimals = 2): string {
    const reference = Math.max(total, used);

    if (reference < 1) {
        return `${formatNumber(0)} / ${formatNumber(0)} ${unitLabel(0)}`;
    }

    const i = byteUnitIndex(reference);

    return `${formatNumber(scaleBytes(Math.max(0, used), i, decimals))} / ${formatNumber(scaleBytes(total, i, decimals))} ${unitLabel(i)}`;
}

function percentageToString(value: number, decimals = 2): string {
    return new Intl.NumberFormat(i18next.language || 'en', {
        style: 'percent',
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
        useGrouping: false,
    }).format(value / 100);
}

/** Wraps IPv6 addresses in brackets. */
function ip(value: string): string {
    // noinspection RegExpSimplifiable
    return /([a-f0-9:]+:+)+[a-f0-9]+/.test(value) ? `[${value}]` : value;
}

export { ip, mbToBytes, bytesToString, bytesRatioToString, percentageToString };
