import format from 'date-fns/format';

/**
 * Return the human readable filesize for a given number of bytes. This
 * uses 1024 as the base, so the response is denoted accordingly.
 *
 * @param {Number} bytes
 * @return {String}
 */
export function readableSize (bytes) {
    if (Math.abs(bytes) < 1024) {
        return `${bytes} Bytes`;
    }

    let u = -1;
    const units = ['KiB', 'MiB', 'GiB', 'TiB'];

    do {
        bytes /= 1024;
        u++;
    } while (Math.abs(bytes) >= 1024 && u < units.length - 1);

    return `${bytes.toFixed(1)} ${units[u]}`;
}

/**
 * Format the given date as a human readable string.
 *
 * @param {String} date
 * @return {String}
 */
export function formatDate (date) {
    return format(date, 'MMM D, YYYY [at] HH:MM');
}
