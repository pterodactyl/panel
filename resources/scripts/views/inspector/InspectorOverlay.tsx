import { useEffect, useReducer, useRef, useState, useSyncExternalStore } from 'react';
import { createPortal } from 'react-dom';
import { getViewProbe } from '@/views/inspect';
import { cn } from '@/lib/cn';
import { getInspectableFrames, subscribeInspectableFrames } from './frames';
import { closestView, isElement, viewRect } from './geometry';
import { InspectorPanel } from './InspectorPanel';
import { renderingLayer } from './snippets';

/** Pointer events the page must not see while the inspector picks views. */
const BLOCKED = ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'dblclick', 'contextmenu'] as const;

const inInspector = (target: EventTarget | null) => isElement(target) && !!target.closest('[data-inspector]');

function Outline({ element, pinned }: { element: Element; pinned?: boolean }) {
    const rect = viewRect(element);
    const name = element.getAttribute('data-view');
    const probe = getViewProbe(element);
    const layer = probe && renderingLayer(probe.current());

    if (!rect) {
        return null;
    }

    return (
        <div
            className={cn(
                'fixed rounded-xs border-2',
                pinned ? 'border-accent bg-accent/5' : 'border-primary bg-primary/10'
            )}
            style={{ left: rect.left, top: rect.top, width: rect.width, height: rect.height }}
        >
            {!pinned && (
                <span
                    className={cn(
                        'absolute left-[-2px] whitespace-nowrap rounded-xs bg-primary px-1.5 py-0.5 font-mono text-xs text-primary-foreground',
                        rect.top > 24 ? 'bottom-full mb-0.5' : 'top-full mt-0.5'
                    )}
                >
                    {name}
                    {layer && layer.source !== 'classic' && <span className='opacity-75'> · {layer.id}</span>}
                </span>
            )}
        </div>
    );
}

/** Outlines views under the pointer and pins one in the panel; loaded only while inspecting. */
export default function InspectorOverlay({ onExit }: { onExit: () => void }) {
    const [hovered, setHovered] = useState<Element | null>(null);
    const [pinned, setPinned] = useState<Element | null>(null);
    const [, redraw] = useReducer((count: number) => count + 1, 0);
    const frames = useSyncExternalStore(subscribeInspectableFrames, getInspectableFrames);
    const pinnedRef = useRef(pinned);
    const exitRef = useRef(onExit);

    pinnedRef.current = pinned;
    exitRef.current = onExit;

    useEffect(() => {
        const documents = [document, ...frames.flatMap((frame) => frame.contentDocument ?? [])];
        let frame = 0;
        const schedule = () => {
            frame ||= requestAnimationFrame(() => {
                frame = 0;
                redraw();
            });
        };

        const onMove = (event: PointerEvent) => {
            const { target } = event;

            setHovered(isElement(target) && !inInspector(target) ? closestView(target) : null);
            schedule();
        };

        const block = (event: Event) => {
            if (!inInspector(event.target)) {
                event.preventDefault();
                event.stopPropagation();
            }
        };

        const onClick = (event: MouseEvent) => {
            if (inInspector(event.target)) {
                return;
            }

            block(event);
            const view = isElement(event.target) ? closestView(event.target) : null;

            if (view) {
                setPinned(view);
            }
        };

        const onKey = (event: KeyboardEvent) => {
            if (event.key !== 'Escape') {
                return;
            }

            event.preventDefault();
            if (pinnedRef.current) {
                setPinned(null);
            } else {
                exitRef.current();
            }
        };

        const cursor = Object.assign(document.createElement('style'), {
            textContent: '* { cursor: crosshair !important; } [data-inspector] * { cursor: auto !important; }',
        });

        document.head.append(cursor);
        for (const target of documents) {
            target.addEventListener('pointermove', onMove, true);
            target.addEventListener('click', onClick, true);
            target.addEventListener('keydown', onKey, true);
            target.addEventListener('scroll', schedule, true);
            for (const type of BLOCKED) {
                target.addEventListener(type, block, true);
            }
        }

        window.addEventListener('resize', schedule);

        return () => {
            cancelAnimationFrame(frame);
            cursor.remove();
            window.removeEventListener('resize', schedule);
            for (const target of documents) {
                target.removeEventListener('pointermove', onMove, true);
                target.removeEventListener('click', onClick, true);
                target.removeEventListener('keydown', onKey, true);
                target.removeEventListener('scroll', schedule, true);
                for (const type of BLOCKED) {
                    target.removeEventListener(type, block, true);
                }
            }
        };
    }, [frames]);

    return createPortal(
        <div data-inspector className='pointer-events-none fixed inset-0 z-[2147483000]'>
            {pinned?.isConnected && <Outline element={pinned} pinned />}
            {hovered && hovered !== pinned && hovered.isConnected && <Outline element={hovered} />}
            <div
                role='status'
                className='pointer-events-auto fixed top-3 left-1/2 flex -translate-x-1/2 items-center gap-3 rounded-full border border-border bg-popover px-4 py-1.5 text-xs text-popover-foreground shadow-lg'
            >
                <span>
                    <strong className='font-semibold'>Inspecting views.</strong> Click one to pin it, Esc to{' '}
                    {pinned ? 'unpin' : 'stop'}.
                </span>
                <button type='button' className='text-accent hover:underline' onClick={onExit}>
                    Stop
                </button>
            </div>
            {pinned && <InspectorPanel element={pinned} onPin={setPinned} onClose={() => setPinned(null)} />}
        </div>,
        document.body
    );
}
