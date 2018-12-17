import { format } from 'date-fns';

/**
 * Return the human readable filesize for a given number of bytes. This
 * uses 1024 as the base, so the response is denoted accordingly.
 */
export function readableSize (bytes: number): string {
    if (Math.abs(bytes) < 1024) {
        return `${bytes} Bytes`;
    }

    let u: number = -1;
    const units: Array<string> = ['KiB', 'MiB', 'GiB', 'TiB'];

    do {
        bytes /= 1024;
        u++;
    } while (Math.abs(bytes) >= 1024 && u < units.length - 1);

    return `${bytes.toFixed(1)} ${units[u]}`;
}

/**
 * Format the given date as a human readable string.
 */
export function formatDate (date: string): string {
    return format(date, 'MMM D, YYYY [at] HH:MM');
}
