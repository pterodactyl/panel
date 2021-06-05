import { Terminal, ITerminalAddon } from 'xterm';

export class ScrollDownHelperAddon implements ITerminalAddon {
    private terminal: Terminal = new Terminal();
    private element?: HTMLDivElement;

    activate (terminal: Terminal): void {
        this.terminal = terminal;

        this.terminal.onScroll(() => {
            if (this.isScrolledDown()) {
                this.hide();
            }
        });

        this.terminal.onLineFeed(() => {
            if (!this.isScrolledDown()) {
                this.show();
            }
        });

        this.show();
    }

    dispose (): void {
        // ignore
    }

    show (): void {
        if (!this.terminal || !this.terminal.element) {
            return;
        }
        if (this.element) {
            this.element.style.visibility = 'visible';
            return;
        }

        this.terminal.element.style.position = 'relative';

        this.element = document.createElement('div');
        this.element.innerHTML = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="bell" class="svg-inline--fa fa-bell fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64zm215.39-149.71c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71z"></path></svg>';
        this.element.style.position = 'absolute';
        this.element.style.right = '1.5rem';
        this.element.style.bottom = '.5rem';
        this.element.style.padding = '.5rem';
        this.element.style.fontSize = '1.25em';
        this.element.style.boxShadow = '0 2px 8px #000';
        this.element.style.backgroundColor = '#252526';
        this.element.style.zIndex = '999';
        this.element.style.cursor = 'pointer';

        this.element.addEventListener('click', () => {
            this.terminal.scrollToBottom();
        });

        this.terminal.element.appendChild(this.element);
    }

    hide (): void {
        if (this.element) {
            this.element.style.visibility = 'hidden';
        }
    }

    isScrolledDown (): boolean {
        return this.terminal.buffer.active.viewportY === this.terminal.buffer.active.baseY;
    }
}
