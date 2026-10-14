import dayjs from '@/lib/dayjs';

/** Formats a date with dayjs tokens, advanced ones included, such as `MMM Do [at] h:mmA`. */
export function formatDate(date: string | Date, format: string): string {
    return dayjs(date).format(format);
}

/** How long ago a date was, such as "3 hours ago". */
export function fromNow(date: string | Date): string {
    return dayjs(date).fromNow();
}
