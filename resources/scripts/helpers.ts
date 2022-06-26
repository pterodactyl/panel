export const megabytesToBytes = (mb: number) => Math.floor(mb * 1024 * 1024);

export function bytesToHuman (bytes: number): string {
    if (bytes < 1) {
        return '0 Bytes';
    }

    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return `${Number((bytes / Math.pow(1024, i)).toFixed(2))} ${[ 'Bytes', 'kB', 'MB', 'GB', 'TB' ][i]}`;
}

export function megabytesToHuman (mb: number): string {
    return bytesToHuman(megabytesToBytes(mb));
}

export const randomInt = (low: number, high: number) => Math.floor(Math.random() * (high - low) + low);

export const cleanDirectoryPath = (path: string) => path.replace(/(\/(\/*))|(^$)/g, '/');

export const capitalize = (s: string) => s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();

export function fileBitsToString (mode: string, directory: boolean): string {
    const m = parseInt(mode, 8);

    let buf = '';
    'dalTLDpSugct?'.split('').forEach((c, i) => {
        if ((m & (1 << (32 - 1 - i))) !== 0) {
            buf = buf + c;
        }
    });

    if (buf.length === 0) {
        // If the file is directory, make sure it has the directory flag.
        if (directory) {
            buf = 'd';
        } else {
            buf = '-';
        }
    }

    'rwxrwxrwx'.split('').forEach((c, i) => {
        if ((m & (1 << (9 - 1 - i))) !== 0) {
            buf = buf + c;
        } else {
            buf = buf + '-';
        }
    });

    return buf;
}

/**
 * URL-encodes the segments of a path.
 * This allows to use the path as part of a URL while preserving the slashes.
 * @param path the path to encode
 */
export function encodePathSegments (path: string): string {
    return path.split('/').map(s => encodeURIComponent(s)).join('/');
}

export function hashToPath (hash: string): string {
    return hash.length > 0 ? decodeURIComponent(hash.substr(1)) : '/';
}

export function formatIp (ip: string): string {
    return /([a-f0-9:]+:+)+[a-f0-9]+/.test(ip) ? `[${ip}]` : ip;
}

// eslint-disable-next-line @typescript-eslint/ban-types
export const isObject = (o: unknown): o is {} => typeof o === 'object' && o !== null;

// eslint-disable-next-line @typescript-eslint/ban-types
export const isEmptyObject = (o: {}): boolean =>
    Object.keys(o).length === 0 && Object.getPrototypeOf(o) === Object.prototype;

// eslint-disable-next-line @typescript-eslint/ban-types
export const getObjectKeys = <T extends {}> (o: T): (keyof T)[] => Object.keys(o) as (keyof typeof o)[];

export const toRGBA = (hex: string, alpha = 1): string => {
    const [ r, g, b ] = hex.match(/\w\w/g)!.map(v => parseInt(v, 16));

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};
