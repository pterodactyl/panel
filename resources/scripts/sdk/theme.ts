import * as theme from '@/lib/theme';

/**
 * Calls `listener`, at most once a frame, after a stylesheet or an `<html>` attribute changes.
 * It is a hint, not a diff: compare the values you read. Returns the unsubscribe function.
 */
export function onThemeChange(listener: () => void): () => void {
    return theme.onThemeChange(listener);
}

/** Report a token change the DOM cannot reveal (CSSOM rule edits, adopted stylesheets). */
export function notifyThemeChange(): void {
    theme.notifyThemeChange();
}

/** The current value of a token on `<html>`, e.g. `readThemeToken('--chart-1')`; '' when unset. */
export function readThemeToken(name: `--${string}`): string {
    return theme.readThemeToken(name);
}
