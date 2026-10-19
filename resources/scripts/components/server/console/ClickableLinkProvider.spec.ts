/** @vitest-environment jsdom */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { ILink, ILinkProvider } from 'ghostty-web';
import { ClickableLinkProvider } from './ClickableLinkProvider';

const linkTo = (text: string): ILink => ({
    text,
    range: { start: { x: 0, y: 0 }, end: { x: text.length - 1, y: 0 } },
    activate: vi.fn(),
});

const sourceOf = (...links: ILink[]): ILinkProvider => ({
    provideLinks: (_y, callback) => callback(links.length ? links : undefined),
    dispose: vi.fn(),
});

const provide = (provider: ILinkProvider) => {
    let provided: ILink[] | undefined;

    provider.provideLinks(0, (links) => {
        provided = links;
    });

    return provided;
};

describe('ClickableLinkProvider', () => {
    let selected: boolean;
    const terminal = { hasSelection: () => selected };

    beforeEach(() => {
        selected = false;
        vi.spyOn(window, 'open').mockReturnValue(null);
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('opens a web link only on a Ctrl or Cmd click', () => {
        const [link] = provide(new ClickableLinkProvider(terminal, sourceOf(linkTo('https://example.com/a')))) ?? [];

        link.activate(new MouseEvent('click'));
        expect(window.open).not.toHaveBeenCalled();

        link.activate(new MouseEvent('click', { ctrlKey: true }));
        link.activate(new MouseEvent('click', { metaKey: true }));
        expect(window.open).toHaveBeenCalledTimes(2);
        expect(window.open).toHaveBeenCalledWith('https://example.com/a', '_blank', 'noopener,noreferrer');
    });

    it('keeps the range of the source link', () => {
        const source = linkTo('http://example.com');
        const [link] = provide(new ClickableLinkProvider(terminal, sourceOf(source))) ?? [];

        expect(link.range).toEqual(source.range);
    });

    it('opens nothing when the click ends a drag-selection', () => {
        const [link] = provide(new ClickableLinkProvider(terminal, sourceOf(linkTo('https://example.com')))) ?? [];

        selected = true;
        link.activate(new MouseEvent('click', { ctrlKey: true }));

        expect(window.open).not.toHaveBeenCalled();
    });

    it('drops links that are not http or https', () => {
        const web = linkTo('https://example.com');
        const links = provide(
            new ClickableLinkProvider(
                terminal,
                sourceOf(linkTo('javascript:alert(1)'), linkTo('ssh://host'), linkTo('not a url'), web)
            )
        );

        expect(links?.map((link) => link.text)).toEqual(['https://example.com']);
        expect(provide(new ClickableLinkProvider(terminal, sourceOf(linkTo('file:///etc/passwd'))))).toBeUndefined();
    });

    it('provides nothing for a row without links', () => {
        expect(provide(new ClickableLinkProvider(terminal, sourceOf()))).toBeUndefined();
    });

    it('disposes the source provider', () => {
        const source = sourceOf();

        new ClickableLinkProvider(terminal, source).dispose();

        expect(source.dispose).toHaveBeenCalled();
    });
});
