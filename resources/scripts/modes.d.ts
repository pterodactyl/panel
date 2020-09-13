export interface Mode {
    name: string,
    mime: string,
    mimes?: string[],
    mode: string,
    ext: string[],
    alias?: string[],
    file?: RegExp,
}

declare const modes: Mode[];

export default modes;
