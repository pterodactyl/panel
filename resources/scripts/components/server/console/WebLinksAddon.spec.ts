import { describe, expect, it, vi } from 'vitest';
import type { ILink, ILinkProvider } from 'ghostty-web';
import { ClickableLinkProvider } from './ClickableLinkProvider';
import { WebLinksAddon } from './WebLinksAddon';

const linkTo = (text: string): ILink => ({
    text,
    range: { start: { x: 0, y: 0 }, end: { x: text.length - 1, y: 0 } },
    activate: vi.fn(),
});

const providerOf = (...links: ILink[]): ILinkProvider => ({
    provideLinks: (_y, callback) => callback(links.length ? links : undefined),
    dispose: vi.fn(),
});

const createTerminal = (text = '') => {
    const builtIns = [providerOf(), providerOf()];

    return {
        builtIns,
        linkDetector: { providers: [...builtIns], invalidateCache: vi.fn() },
        hasSelection: () => false,
        getScrollbackLength: () => 1,
        buffer: {
            active: {
                getLine: (y: number) =>
                    y === 0
                        ? {
                              length: text.length,
                              isWrapped: false,
                              getCell: (x: number) =>
                                  x < 0 || x >= text.length
                                      ? undefined
                                      : { getCodepoint: () => text.codePointAt(x) ?? 0 },
                          }
                        : undefined,
            },
        },
    };
};

const textsFrom = (provider: ILinkProvider | undefined) => {
    let provided: ILink[] | undefined;

    provider?.provideLinks(0, (links) => {
        provided = links;
    });

    return provided?.map((link) => link.text);
};

describe('WebLinksAddon', () => {
    it("swaps Ghostty's link providers for ones that only open web links", () => {
        const terminal = createTerminal('see https://example.com/a');
        const hyperlinks = providerOf(linkTo('javascript:alert(1)'), linkTo('https://osc8.example'));

        new WebLinksAddon(terminal as never, hyperlinks).activate();

        const [osc8, urls] = terminal.linkDetector.providers;

        expect(terminal.linkDetector.providers).toHaveLength(2);
        expect(osc8).toBeInstanceOf(ClickableLinkProvider);
        expect(urls).toBeInstanceOf(ClickableLinkProvider);
        expect(textsFrom(osc8)).toEqual(['https://osc8.example']);
        expect(textsFrom(urls)).toEqual(['https://example.com/a']);
        expect(terminal.linkDetector.invalidateCache).toHaveBeenCalled();
    });

    it("puts Ghostty's providers back when disposed, keeping any registered since", () => {
        const terminal = createTerminal();
        const hyperlinks = providerOf();
        const later = providerOf();
        const addon = new WebLinksAddon(terminal as never, hyperlinks);

        addon.activate();
        terminal.linkDetector.providers.push(later);
        addon.dispose();

        const [first, second, third] = terminal.linkDetector.providers;

        expect(terminal.linkDetector.providers).toHaveLength(3);
        expect(first).toBe(terminal.builtIns[0]);
        expect(second).toBe(terminal.builtIns[1]);
        expect(third).toBe(later);
        expect(hyperlinks.dispose).toHaveBeenCalled();
    });

    it('leaves a terminal without a link detector alone', () => {
        const terminal = createTerminal();

        Reflect.deleteProperty(terminal, 'linkDetector');

        const addon = new WebLinksAddon(terminal as never, providerOf());

        expect(() => {
            addon.activate();
            addon.dispose();
        }).not.toThrow();
    });
});
