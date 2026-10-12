import { useSyncExternalStore } from 'react';
import { onThemeChange, readThemeToken } from '@/lib/theme';

const URL_TOKEN = /^url\(\s*(?:"((?:[^"\\]|\\.)*)"|'((?:[^'\\]|\\.)*)'|([^\s"'()\\]*))\s*\)$/s;
const ESCAPE = /\\(?:([0-9a-fA-F]{1,6})\s?|(.))/gs;

const codePoint = (hex: string) => {
    const value = Number.parseInt(hex, 16);

    return String.fromCodePoint(value > 0 && value <= 0x10ffff ? value : 0xfffd);
};

/** The address in a `url(…)` token value, with CSS escapes undone; null for any other value. */
export function cssUrl(value: string): string | null {
    const match = URL_TOKEN.exec(value.trim());
    const raw = match?.[1] ?? match?.[2] ?? match?.[3];

    return raw
        ? raw.replaceAll(ESCAPE, (_, hex?: string, char?: string) => (hex ? codePoint(hex) : (char ?? '')))
        : null;
}

let logo: string | null | undefined;

const readLogo = () => cssUrl(readThemeToken('--brand-logo'));

function subscribeLogo(listener: () => void): () => void {
    return onThemeChange(() => {
        logo = readLogo();
        listener();
    });
}

function currentLogo(): string | null {
    if (logo === undefined) {
        logo = readLogo();
    }

    return logo;
}

/**
 * The logo of the active theme: the `--brand-logo` token its image setting writes. It follows the
 * admin's live preview of the theme settings.
 */
export function useBrandLogo(): string | null {
    return useSyncExternalStore(subscribeLogo, currentLogo, () => null);
}
