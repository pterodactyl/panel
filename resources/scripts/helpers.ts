export const bytesToMegabytes = (bytes: number) => Math.floor(bytes / 1024 / 1024);

export const megabytesToBytes = (mb: number) => Math.floor(mb * 1024 * 1024);

export function bytesToHuman (bytes: number): string {
    if (bytes === 0) {
        return '0 kB';
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

export const base64Decode = (input: string): string => {
    input = input.replace(/-/g, '+').replace(/_/g, '/');
    const pad = input.length % 4;
    if (pad) {
        if (pad === 1) {
            throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
        }
        input += new Array(5 - pad).join('=');
    }
    return input;
};

export const bufferDecode = (value: string): ArrayBuffer => Uint8Array.from(window.atob(value), c => c.charCodeAt(0));

// @ts-ignore
export const bufferEncode = (value: ArrayBuffer): string => window.btoa(String.fromCharCode.apply(null, new Uint8Array(value)));

export const decodeSecurityKeyCredentials = (credentials: PublicKeyCredentialDescriptor[]) => credentials.map(c => ({
    id: bufferDecode(base64Decode(c.id.toString())),
    type: c.type,
    transports: c.transports,
}));
