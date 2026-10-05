import { describe, expect, it } from 'vitest';
import modes from '@/modes';
import { resolveCodemirrorLanguage } from '@/components/elements/codemirror/languages';

describe('resolveCodemirrorLanguage', () => {
    it('resolves every configured editor mode without throwing', () => {
        for (const mode of modes) {
            expect(() => resolveCodemirrorLanguage(mode.mime)).not.toThrow();
        }
    });

    it('returns plain text for text/plain', () => {
        expect(resolveCodemirrorLanguage('text/plain')).toEqual([]);
    });

    it.each(['application/json', 'application/typescript', 'text/x-php', 'text/x-sql', 'text/x-yaml'])(
        'resolves native language support for %s',
        (mime) => {
            expect(resolveCodemirrorLanguage(mime)).not.toEqual([]);
        }
    );

    it.each(['text/x-dockerfile', 'text/x-nginx-conf', 'text/x-sh', 'text/x-toml'])(
        'resolves legacy language support for %s',
        (mime) => {
            expect(resolveCodemirrorLanguage(mime)).not.toEqual([]);
        }
    );
});
