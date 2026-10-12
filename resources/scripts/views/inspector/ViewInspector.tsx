import { lazy, Suspense, useEffect, useState, type ReactNode } from 'react';
import { ScanSearch } from 'lucide-react';
import { getBootstrapTheme } from '@/bootstrap';
import { cn } from '@/lib/cn';
import { ViewInspectionContext, type ViewInspection } from '@/views/inspect';

const InspectorOverlay = lazy(() => import('./InspectorOverlay'));
const inspection: ViewInspection = { wrap: true };

/** Alt+Shift+I, by key position so macOS's dead keys do not get in the way. */
export const isInspectorShortcut = (event: KeyboardEvent): boolean =>
    event.altKey && event.shiftKey && !event.ctrlKey && !event.metaKey && event.code === 'KeyI';

function InspectorRoot({ children }: { children?: ReactNode }) {
    const [on, setOn] = useState(false);

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if (isInspectorShortcut(event)) {
                event.preventDefault();
                setOn((current) => !current);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, []);

    return (
        <>
            <ViewInspectionContext.Provider value={on ? inspection : null}>{children}</ViewInspectionContext.Provider>
            <button
                type='button'
                data-inspector
                aria-pressed={on}
                aria-label='Inspect views'
                title='Inspect views (Alt+Shift+I)'
                className={cn(
                    'fixed bottom-4 left-4 z-[2147483001] flex h-9 w-9 items-center justify-center rounded-full border shadow-lg transition-colors',
                    on
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-popover text-muted-foreground opacity-60 hover:opacity-100'
                )}
                onClick={() => setOn((current) => !current)}
            >
                <ScanSearch className='h-4 w-4' aria-hidden />
            </button>
            {on && (
                <Suspense fallback={null}>
                    <InspectorOverlay onExit={() => setOn(false)} />
                </Suspense>
            )}
        </>
    );
}

/**
 * The view inspector, when the server allows it (debug mode, or a root admin with `?inspect=1`).
 * Off, it renders no wrappers and listens only for its shortcut.
 */
export function ViewInspector({ children }: { children?: ReactNode }) {
    return getBootstrapTheme().inspect ? <InspectorRoot>{children}</InspectorRoot> : children;
}
