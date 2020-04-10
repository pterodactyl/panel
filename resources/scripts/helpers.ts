export function bytesToHuman (bytes: number): string {
    if (bytes === 0) {
        return '0 kB';
    }

    const i = Math.floor(Math.log(bytes) / Math.log(1000));
    // @ts-ignore
    return `${(bytes / Math.pow(1000, i)).toFixed(2) * 1} ${[ 'Bytes', 'kB', 'MB', 'GB', 'TB' ][i]}`;
}

export const bytesToMegabytes = (bytes: number) => Math.floor(bytes / 1000 / 1000);

export const randomInt = (low: number, high: number) => Math.floor(Math.random() * (high - low) + low);

export const cleanDirectoryPath = (path: string) => path.replace(/(^#\/*)|(\/(\/*))|(^$)/g, '/');
