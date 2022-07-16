import { Terminal, ITerminalAddon } from 'xterm';

export class ScrollDownHelperAddon implements ITerminalAddon {
    private terminal: Terminal = new Terminal();
    private element?: HTMLDivElement;

    activate(terminal: Terminal): void {
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

    dispose(): void {
        // ignore
    }

    show(): void {
        if (!this.terminal || !this.terminal.element) {
            return;
        }
        if (this.element) {
            this.element.style.visibility = 'visible';
            return;
        }

        this.terminal.element.style.position = 'relative';

        this.element = document.createElement('div');
        this.element.innerHTML =
            '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="arrow-down" class="svg-inline--fa fa-bell fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M374.6 310.6l-160 160C208.4 476.9 200.2 480 192 480s-16.38-3.125-22.62-9.375l-160-160c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 370.8V64c0-17.69 14.33-31.1 31.1-31.1S224 46.31 224 64v306.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0S387.1 298.1 374.6 310.6z"/></svg>';
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

    hide(): void {
        if (this.element) {
            this.element.style.visibility = 'hidden';
        }
    }

    isScrolledDown(): boolean {
        return this.terminal.buffer.active.viewportY === this.terminal.buffer.active.baseY;
    }
}
