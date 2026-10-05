import { normalize } from 'pathe';

export const randomInt = (low: number, high: number) => Math.floor(Math.random() * (high - low) + low);

export const cleanDirectoryPath = (path: string) => path.replace(/(\/(\/*))|(^$)/g, '/');

export function fileBitsToString(mode: string, directory: boolean): string {
    const m = parseInt(mode, 8);

    let buf = '';
    'dalTLDpSugct?'.split('').forEach((c, i) => {
        if ((m & (1 << (32 - 1 - i))) !== 0) {
            buf = buf + c;
        }
    });

    if (buf.length === 0) {
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

export function encodePathSegments(path: string): string {
    return path
        .split('/')
        .map((s) => encodeURIComponent(s))
        .join('/');
}

export function hashToPath(hash: string): string {
    const path = decodeURIComponent(hash.replace(/^#/, ''));
    const segments = path.split('/').filter(Boolean);

    return segments.length > 0 ? `/${segments.join('/')}` : '/';
}

/** Strips leading "../" and "/" segments. */
export const normalizeServerPath = (path: string): string => normalize(path).replace(/^(\.\.\/|\/)+/, '');

/** The name a newly created directory appears under in its parent's file list. */
export const newDirectoryDisplayName = (name: string): string => normalizeServerPath(name).split('/', 1)[0] || name;
