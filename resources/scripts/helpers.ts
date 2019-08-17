export function bytesToHuman (bytes: number): string {
    if (bytes === 0) {
        return '0 kB';
    }

    const i = Math.floor(Math.log(bytes) / Math.log(1000));

    // @ts-ignore
    return `${(bytes / Math.pow(1000, i)).toFixed(2) * 1} ${['Bytes', 'kB', 'MB', 'GB', 'TB'][i]}`;
}
