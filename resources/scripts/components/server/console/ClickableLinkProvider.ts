import type { ILink, ILinkProvider, Terminal } from 'ghostty-web';

const OPENABLE_PROTOCOLS = new Set(['http:', 'https:']);

const isOpenable = (url: string) => {
    try {
        return OPENABLE_PROTOCOLS.has(new URL(url).protocol);
    } catch {
        return false;
    }
};

export class ClickableLinkProvider implements ILinkProvider {
    constructor(
        private readonly terminal: Pick<Terminal, 'hasSelection'>,
        private readonly source: ILinkProvider
    ) {}

    provideLinks(y: number, callback: (links: ILink[] | undefined) => void) {
        this.source.provideLinks(y, (links) => {
            const openable = links?.filter((link) => isOpenable(link.text)).map((link) => this.clickable(link));

            callback(openable?.length ? openable : undefined);
        });
    }

    dispose() {
        this.source.dispose?.();
    }

    private clickable(link: ILink): ILink {
        return {
            ...link,
            activate: (event) => {
                if ((event.ctrlKey || event.metaKey) && !this.terminal.hasSelection()) {
                    window.open(link.text, '_blank', 'noopener,noreferrer');
                }
            },
        };
    }
}
