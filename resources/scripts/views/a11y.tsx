import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import { useRouter } from '@tanstack/react-router';
import { cn } from '@/lib/cn';

const InsideMain = createContext(false);

const focusMain = (options?: FocusOptions) => document.getElementById('main')?.focus(options);

/**
 * The routed content of a layout and the target of the skip link. Layouts nest, so only the
 * outermost renders the `<main>` element; a nested one with a `className` renders a `<div>` with it.
 */
export function Main({ className, children }: { className?: string; children?: ReactNode }) {
    const nested = useContext(InsideMain);

    if (nested) {
        return className ? <div className={className}>{children}</div> : children;
    }

    return (
        <InsideMain.Provider value>
            <main id='main' tabIndex={-1} className={cn('outline-none', className)}>
                {children}
            </main>
        </InsideMain.Provider>
    );
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
