import { useEffect, useEffectEvent } from 'react';

/** How a shortcut is named on this device. */
export interface Shortcut {
    /** For `aria-keyshortcuts`: `Meta+/` on Apple devices, `Control+/` elsewhere. */
    readonly aria: string;
    /** For a hint: `⌘/` on Apple devices, `Ctrl+/` elsewhere. */
    readonly label: string;
}

const apple = /Mac|iPhone|iPad|iPod/.test(navigator.userAgent);

// Keys pressed in these belong to them: text fields, and the panel's dialogs and menus.
const owners =
    'input, textarea, select, [contenteditable], [role="textbox"], [role="combobox"], [role="dialog"], [role="alertdialog"], [role="menu"], [role="listbox"]';

const parse = (keys: string) => ({ mod: keys.startsWith('mod+'), key: keys.replace(/^mod\+/, '') });

/** Names `keys`, such as `mod+/` or `g`, as this device does. */
export function describeShortcut(keys: string): Shortcut {
    const { mod, key } = parse(keys);
    const name = key.length === 1 ? key.toUpperCase() : key;

    if (!mod) {
        return { aria: name, label: name };
    }

    return apple ? { aria: `Meta+${name}`, label: `⌘${name}` } : { aria: `Control+${name}`, label: `Ctrl+${name}` };
}

/**
 * Calls `handler` when `keys` is pressed: a key as `KeyboardEvent.key` names it, such as `g`, `1` or
 * `/`, after `mod+` for one with Cmd on Apple devices and Ctrl elsewhere. Keys typed in a field, a
 * dialog or a menu are theirs, and a key another handler took first is skipped, so the panel's own
 * `mod+/` (search) keeps working. `when: false` turns it off. Returns the shortcut's names.
 */
export function useShortcut(
    keys: string,
    handler: (event: KeyboardEvent) => void,
    { when = true }: { when?: boolean } = {}
): Shortcut {
    const onKey = useEffectEvent(handler);

    useEffect(() => {
        if (!when) {
            return;
        }

        const { mod, key } = parse(keys);
        const listener = (event: KeyboardEvent) => {
            const pressed = (apple ? event.metaKey : event.ctrlKey) === mod && !(apple ? event.ctrlKey : event.metaKey);

            if (
                event.defaultPrevented ||
                event.repeat ||
                event.altKey ||
                !pressed ||
                event.key.toLowerCase() !== key.toLowerCase() ||
                (event.target instanceof Element && event.target.closest(owners))
            ) {
                return;
            }

            event.preventDefault();
            onKey(event);
        };

        window.addEventListener('keydown', listener);

        return () => window.removeEventListener('keydown', listener);
    }, [keys, when]);

    return describeShortcut(keys);
}
