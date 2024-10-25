/**
 * Takes a string and splits it into an array by whitespace, ignoring any
 * text that is wrapped in quotes. You must escape quotes within a quoted
 * string, otherwise it will just split on those.
 *
 * Derived from https://stackoverflow.com/a/46946420
 */
export default (str: string): string[] => {
    let quoted = false;
    const parts = [''] as string[];

    for (const char of str.trim().match(/\\?.|^$/g) || []) {
        if (char === '"') {
            quoted = !quoted;
        } else if (!quoted && char === ' ') {
            parts.push('');
        } else {
            parts[Math.max(parts.length - 1, 0)] += char.replace(/\\(.)/, '$1');
        }
    }

    if (parts.length === 1 && parts[0] === '') {
        return [];
    }

    return parts;
};
