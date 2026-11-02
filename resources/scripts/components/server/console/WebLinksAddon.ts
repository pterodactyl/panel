import type { ILinkProvider, ITerminalAddon, Terminal } from 'ghostty-web';
import { ClickableLinkProvider } from '@/components/server/console/ClickableLinkProvider';
import { WrappedUrlProvider } from '@/components/server/console/WrappedUrlProvider';

type LinkableTerminal = Pick<Terminal, 'buffer' | 'getScrollbackLength' | 'hasSelection'>;

type LinkDetector = { providers?: ILinkProvider[]; invalidateCache?: () => void };

export class WebLinksAddon implements ITerminalAddon {
    private providers: ILinkProvider[] = [];
    private replaced: ILinkProvider[] = [];

    constructor(
        private readonly terminal: LinkableTerminal,
        private readonly hyperlinks: ILinkProvider
    ) {}

    activate() {
        const detector = this.linkDetector();

        if (!detector?.providers) {
            return;
        }

        this.providers = [
            new ClickableLinkProvider(this.terminal, this.hyperlinks),
            new ClickableLinkProvider(this.terminal, new WrappedUrlProvider(this.terminal)),
        ];
        this.replaced = detector.providers.splice(0, detector.providers.length, ...this.providers);
        detector.invalidateCache?.();
    }

    dispose() {
        const detector = this.linkDetector();

        if (detector?.providers) {
            const added = detector.providers.filter((provider) => !this.providers.includes(provider));

            detector.providers.splice(0, detector.providers.length, ...this.replaced, ...added);
            detector.invalidateCache?.();
        }

        for (const provider of this.providers) {
            provider.dispose?.();
        }

        this.providers = [];
        this.replaced = [];
    }

    private linkDetector() {
        return Reflect.get(this.terminal, 'linkDetector') as LinkDetector | undefined;
    }
}
