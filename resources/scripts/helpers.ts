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

export const cleanDirectoryPath = (path: string) => path.replace(/(^#\/*)|(\/(\/*))|(^$)/g, '/');

export const capitalize = (s: string) => s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();
