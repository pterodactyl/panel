import { createContext, useContext, useEffect, useRef, useState, type ReactNode } from 'react';
import { useRouter } from '@tanstack/react-router';
import { cn } from '@/lib/cn';

const InsideMain = createContext(false);
const LayoutProbe = createContext<(() => void) | null>(null);

const focusMain = (options?: FocusOptions) => document.getElementById('main')?.focus(options);

/**
 * The routed content of a layout and the target of the skip link. Layouts nest, so only the
 * outermost renders the `<main>` element.
 */
export function Main({ className, children }: { className?: string; children?: ReactNode }) {
    const nested = useContext(InsideMain);
    const probe = useContext(LayoutProbe);

    useEffect(() => probe?.(), [probe]);

    if (nested) {
        return children;
    }

    return (
        <InsideMain.Provider value>
            <main id='main' tabIndex={-1} className={cn('outline-none', className)}>
                {children}
            </main>
        </InsideMain.Provider>
    );
}

/** In development, warns when the layout view below never places its content in `<Main>`. */
export function LayoutMainCheck({ name, children }: { name: string; children?: ReactNode }) {
    const found = useRef(false);
    const [probe] = useState(() => () => {
        found.current = true;
    });

    useEffect(() => {
        if (!import.meta.env.DEV) {
            return;
        }

        // Lazy layers render later than this effect.
        const timer = setTimeout(() => {
            if (!found.current) {
                console.warn(`[views] The "${name}" layout never rendered <Main>; place its children inside <Main>.`);
            }
        }, 3000);

        return () => clearTimeout(timer);
    }, [name]);

    return <LayoutProbe.Provider value={probe}>{children}</LayoutProbe.Provider>;
}

export function SkipLink() {
    return (
        <a
            href='#main'
            onClick={(event) => {
                // A fragment navigation would reach the router, which reads the hash on some pages.
                event.preventDefault();
                focusMain();
            }}
            className='sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-sm focus:bg-card focus:px-4 focus:py-2 focus:text-foreground'
        >
            Skip to content
        </a>
    );
}

/** Moves focus to `<Main>` after each navigation to another path and announces the new title. */
export function RouteAnnouncer() {
    const router = useRouter();
    const [title, setTitle] = useState('');

    useEffect(
        () =>
            router.subscribe('onRendered', ({ fromLocation, pathChanged }) => {
                if (!fromLocation || !pathChanged) {
                    return;
                }

                // Pages set the document title in an effect after rendering.
                setTimeout(() => {
                    focusMain({ preventScroll: true });
                    setTitle(document.title);
                });
            }),
        [router]
    );

    return (
        <div aria-live='polite' aria-atomic className='sr-only'>
            {title}
        </div>
    );
}
