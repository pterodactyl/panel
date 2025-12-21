// Minimal MD5 implementation (adapted for deterministic hashing of short strings)
// This is a compact implementation suitable for generating Gravatar hashes.
// License: public domain / MIT style (small portable implementation).

function rotl(n: number, s: number) {
    return (n << s) | (n >>> (32 - s));
}

function toBytes(str: string) {
    const bytes = [] as number[];
    for (let i = 0; i < str.length; i++) {
        const code = str.charCodeAt(i);
        if (code < 0x80) bytes.push(code);
        else if (code < 0x800) {
            bytes.push(0xc0 | (code >> 6));
            bytes.push(0x80 | (code & 0x3f));
        } else if (code < 0xd800 || code >= 0xe000) {
            bytes.push(0xe0 | (code >> 12));
            bytes.push(0x80 | ((code >> 6) & 0x3f));
            bytes.push(0x80 | (code & 0x3f));
        } else {
            // surrogate pair
            i++;
            const codePoint = 0x10000 + (((code & 0x3ff) << 10) | (str.charCodeAt(i) & 0x3ff));
            bytes.push(0xf0 | (codePoint >> 18));
            bytes.push(0x80 | ((codePoint >> 12) & 0x3f));
            bytes.push(0x80 | ((codePoint >> 6) & 0x3f));
            bytes.push(0x80 | (codePoint & 0x3f));
        }
    }
    return bytes;
}

function md5(input: string) {
    const bytes = toBytes(input);
    const origLenBits = bytes.length * 8;
    bytes.push(0x80);
    while ((bytes.length % 64) !== 56) bytes.push(0);
    for (let i = 0; i < 8; i++) bytes.push((origLenBits >>> (8 * i)) & 0xff);

    let a = 0x67452301;
    let b = 0xefcdab89;
    let c = 0x98badcfe;
    let d = 0x10325476;

    for (let i = 0; i < bytes.length; i += 64) {
        const chunk = bytes.slice(i, i + 64);
        const M = new Array(16);
        for (let j = 0; j < 16; j++) {
            M[j] = chunk[j * 4] | (chunk[j * 4 + 1] << 8) | (chunk[j * 4 + 2] << 16) | (chunk[j * 4 + 3] << 24);
        }

        let A = a;
        let B = b;
        let C = c;
        let D = d;

        // Round functions
        const F = (X: number, Y: number, Z: number) => (X & Y) | (~X & Z);
        const G = (X: number, Y: number, Z: number) => (X & Z) | (Y & ~Z);
        const H = (X: number, Y: number, Z: number) => X ^ Y ^ Z;
        const I = (X: number, Y: number, Z: number) => Y ^ (X | ~Z);

        const T = new Array(65);
        for (let t = 1; t <= 64; t++) T[t] = Math.floor(Math.abs(Math.sin(t)) * 2 ** 32) >>> 0;

        const round = (func: Function, a0: number, b0: number, c0: number, d0: number, k: number, s: number, iT: number) => {
            const res = (a0 + func(b0, c0, d0) + M[k] + iT) >>> 0;
            return (b0 + rotl(res, s)) >>> 0;
        };

        // 64 operations
        A = round(F, A, B, C, D, 0, 7, T[1]);
        D = round(F, D, A, B, C, 1, 12, T[2]);
        C = round(F, C, D, A, B, 2, 17, T[3]);
        B = round(F, B, C, D, A, 3, 22, T[4]);
        A = round(F, A, B, C, D, 4, 7, T[5]);
        D = round(F, D, A, B, C, 5, 12, T[6]);
        C = round(F, C, D, A, B, 6, 17, T[7]);
        B = round(F, B, C, D, A, 7, 22, T[8]);
        A = round(F, A, B, C, D, 8, 7, T[9]);
        D = round(F, D, A, B, C, 9, 12, T[10]);
        C = round(F, C, D, A, B, 10, 17, T[11]);
        B = round(F, B, C, D, A, 11, 22, T[12]);
        A = round(F, A, B, C, D, 12, 7, T[13]);
        D = round(F, D, A, B, C, 13, 12, T[14]);
        C = round(F, C, D, A, B, 14, 17, T[15]);
        B = round(F, B, C, D, A, 15, 22, T[16]);

        A = round(G, A, B, C, D, 1, 5, T[17]);
        D = round(G, D, A, B, C, 6, 9, T[18]);
        C = round(G, C, D, A, B, 11, 14, T[19]);
        B = round(G, B, C, D, A, 0, 20, T[20]);
        A = round(G, A, B, C, D, 5, 5, T[21]);
        D = round(G, D, A, B, C, 10, 9, T[22]);
        C = round(G, C, D, A, B, 15, 14, T[23]);
        B = round(G, B, C, D, A, 4, 20, T[24]);
        A = round(G, A, B, C, D, 9, 5, T[25]);
        D = round(G, D, A, B, C, 14, 9, T[26]);
        C = round(G, C, D, A, B, 3, 14, T[27]);
        B = round(G, B, C, D, A, 8, 20, T[28]);
        A = round(G, A, B, C, D, 13, 5, T[29]);
        D = round(G, D, A, B, C, 2, 9, T[30]);
        C = round(G, C, D, A, B, 7, 14, T[31]);
        B = round(G, B, C, D, A, 12, 20, T[32]);

        A = round(H, A, B, C, D, 5, 4, T[33]);
        D = round(H, D, A, B, C, 8, 11, T[34]);
        C = round(H, C, D, A, B, 11, 16, T[35]);
        B = round(H, B, C, D, A, 14, 23, T[36]);
        A = round(H, A, B, C, D, 1, 4, T[37]);
        D = round(H, D, A, B, C, 4, 11, T[38]);
        C = round(H, C, D, A, B, 7, 16, T[39]);
        B = round(H, B, C, D, A, 10, 23, T[40]);
        A = round(H, A, B, C, D, 13, 4, T[41]);
        D = round(H, D, A, B, C, 0, 11, T[42]);
        C = round(H, C, D, A, B, 3, 16, T[43]);
        B = round(H, B, C, D, A, 6, 23, T[44]);
        A = round(H, A, B, C, D, 9, 4, T[45]);
        D = round(H, D, A, B, C, 12, 11, T[46]);
        C = round(H, C, D, A, B, 15, 16, T[47]);
        B = round(H, B, C, D, A, 2, 23, T[48]);

        A = round(I, A, B, C, D, 0, 6, T[49]);
        D = round(I, D, A, B, C, 7, 10, T[50]);
        C = round(I, C, D, A, B, 14, 15, T[51]);
        B = round(I, B, C, D, A, 5, 21, T[52]);
        A = round(I, A, B, C, D, 12, 6, T[53]);
        D = round(I, D, A, B, C, 3, 10, T[54]);
        C = round(I, C, D, A, B, 10, 15, T[55]);
        B = round(I, B, C, D, A, 1, 21, T[56]);
        A = round(I, A, B, C, D, 8, 6, T[57]);
        D = round(I, D, A, B, C, 15, 10, T[58]);
        C = round(I, C, D, A, B, 6, 15, T[59]);
        B = round(I, B, C, D, A, 13, 21, T[60]);
        A = round(I, A, B, C, D, 4, 6, T[61]);
        D = round(I, D, A, B, C, 11, 10, T[62]);
        C = round(I, C, D, A, B, 2, 15, T[63]);
        B = round(I, B, C, D, A, 9, 21, T[64]);

        a = (a + A) >>> 0;
        b = (b + B) >>> 0;
        c = (c + C) >>> 0;
        d = (d + D) >>> 0;
    }

    const toHex = (n: number) => {
        let s = '';
        for (let i = 0; i < 4; i++) {
            s += (`0${(n >>> (i * 8) & 0xff).toString(16)}`).slice(-2);
        }
        return s;
    };

    return toHex(a) + toHex(b) + toHex(c) + toHex(d);
}

export function gravatarUrl(value: string | undefined, size = 48) {
    const seed = (value || '').trim().toLowerCase();
    const hash = md5(seed);
    return `https://www.gravatar.com/avatar/${hash}?s=${size}&d=identicon`;
}

export default md5;
