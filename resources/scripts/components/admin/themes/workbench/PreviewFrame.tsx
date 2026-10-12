import { useEffect, useRef, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { onThemeChange } from '@/lib/theme';
import type { ThemeScheme } from '@/views/theme';
import { registerInspectableFrame } from '@/views/inspector/frames';
import { isElement } from '@/views/inspector/geometry';

const FRAME_DOCUMENT =
    '<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0}#root{display:flow-root}</style></head><body><div id="root"></div></body></html>';
const MAX_HEIGHT = 4000;

const isStylesheet = (node: Element): node is HTMLLinkElement | HTMLStyleElement =>
    node instanceof HTMLStyleElement || (node instanceof HTMLLinkElement && node.relList.contains('stylesheet'));

/**
 * Keeps a frame's stylesheets, `<html>` attributes and body class equal to the panel's, so the active
 * theme and any live setting preview apply, with the frame's own colour scheme.
 */
export function mirrorDocument(target: Document, scheme: ThemeScheme, clones: Map<Element, Element>): void {
    const sources = [...document.head.children].filter(isStylesheet);

    for (const [source, clone] of clones) {
        if (!sources.some((candidate) => candidate === source)) {
            clone.remove();
            clones.delete(source);
        }
    }

    for (const source of sources) {
        const clone = clones.get(source);

        if (clone?.outerHTML !== source.outerHTML) {
            const fresh = target.importNode(source, true);

            clones.set(source, fresh);
            if (clone) {
                clone.replaceWith(fresh);
            } else {
                target.head.append(fresh);
            }
        }
    }

    const html = target.documentElement;

    for (const name of html.getAttributeNames()) {
        html.removeAttribute(name);
    }

    for (const { name, value } of document.documentElement.attributes) {
        html.setAttribute(name, value);
    }

    html.classList.remove('dark', 'light');
    html.classList.add(scheme);
    target.body.className = document.body.className;
}

/** Links and forms inside a preview must not navigate the panel around it. */
function holdNavigation(event: Event) {
    if (event.type === 'submit' || (isElement(event.target) && event.target.closest('a[href]'))) {
        event.preventDefault();
    }
}

export interface PreviewFrameProps {
    label: string;
    /** CSS pixels the frame is wide, laid out at that viewport width. */
    width: number;
    /** Below 1 when the frame is shrunk to fit beside the workbench panels. */
    scale: number;
    scheme: ThemeScheme;
    children?: ReactNode;
}

/**
 * Renders its children in a same-origin frame, so media queries see the preset viewport width and the
 * scheme applies to the preview alone. The children stay in this React tree and keep its context.
 */
export default function PreviewFrame({ label, width, scale, scheme, children }: PreviewFrameProps) {
    const [frame, setFrame] = useState<HTMLIFrameElement | null>(null);
    const [root, setRoot] = useState<HTMLElement | null>(null);
    const [height, setHeight] = useState(0);
    const schemeRef = useRef(scheme);

    useEffect(() => {
        if (!frame) {
            return;
        }

        let stop = () => {};

        const ready = () => {
            const target = frame.contentDocument;
            const mount = target?.getElementById('root');

            if (!target || !mount) {
                return;
            }

            stop();

            const clones = new Map<Element, Element>();
            const sync = () => mirrorDocument(target, schemeRef.current, clones);
            const measure = () => setHeight(Math.min(Math.ceil(mount.getBoundingClientRect().height), MAX_HEIGHT));
            const observer = new ResizeObserver(measure);
            // Resize observations wait for rendering, which a hidden tab skips; mutations do not.
            const mutations = new MutationObserver(measure);

            sync();
            observer.observe(mount);
            mutations.observe(mount, { childList: true, subtree: true, attributes: true, characterData: true });
            target.addEventListener('click', holdNavigation, true);
            target.addEventListener('submit', holdNavigation, true);

            const unwatch = onThemeChange(sync);
            const unregister = registerInspectableFrame(frame);

            stop = () => {
                unwatch();
                unregister();
                observer.disconnect();
                mutations.disconnect();
                target.removeEventListener('click', holdNavigation, true);
                target.removeEventListener('submit', holdNavigation, true);
            };

            setRoot(mount);
        };

        frame.addEventListener('load', ready);
        // The srcdoc may have loaded before this effect ran.
        ready();

        return () => {
            frame.removeEventListener('load', ready);
            stop();
        };
    }, [frame]);

    useEffect(() => {
        schemeRef.current = scheme;

        const html = root?.ownerDocument.documentElement;

        html?.classList.remove('dark', 'light');
        html?.classList.add(scheme);
    }, [root, scheme]);

    return (
        <div className='overflow-hidden' style={{ width: width * scale, height: height * scale }}>
            <iframe
                ref={setFrame}
                title={label}
                srcDoc={FRAME_DOCUMENT}
                className='block border-0 bg-background'
                style={{
                    width,
                    height,
                    transform: scale < 1 ? `scale(${scale})` : undefined,
                    transformOrigin: '0 0',
                }}
            />
            {root && createPortal(children, root)}
        </div>
    );
}
