import dayjs from '@/lib/dayjs';

/** Formats a date with a dayjs format string, such as `formatDate(value, 'MMM D, YYYY HH:mm')`. */
export function formatDate(value: string | Date, format: string): string {
    return dayjs(value).format(format);
}

/** How long ago a date was, such as "3 minutes ago". */
export function fromNow(value: string | Date): string {
    return dayjs(value).fromNow();
}
