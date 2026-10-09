import { useCallback, useEffect, useRef } from 'react';
import { useBlocker, type ShouldBlockFn } from '@tanstack/react-router';
import { routeParamStrings } from '@/router/params';

export interface NavigationBlockerLocation {
    readonly pathname: string;
    readonly params: Readonly<Record<string, string>>;
    readonly search: unknown;
}
export interface NavigationTransition {
    readonly action: 'PUSH' | 'REPLACE' | 'BACK' | 'FORWARD' | 'GO';
    readonly current: NavigationBlockerLocation;
    readonly next: NavigationBlockerLocation;
}
export interface NavigationBlockerOptions {
    /** Text of the default confirmation. Browsers show their own text when the tab closes. */
    message?: string;
    /** Also ask before the tab closes or reloads. Defaults to true. */
    beforeUnload?: boolean;
    /** Decide instead of the default confirmation; resolve true to leave the page. */
    confirm?: (transition: NavigationTransition) => boolean | Promise<boolean>;
}

export const UNSAVED_CHANGES_MESSAGE = 'You have unsaved changes. Leave this page and discard them?';

/** Asks before leaving while `shouldBlock` holds, including back/forward and tab close. */
export function useNavigationBlocker(
    shouldBlock: boolean | (() => boolean),
    options: NavigationBlockerOptions = {}
): void {
    const latest = useRef({ shouldBlock, options });

    useEffect(() => {
        latest.current = { shouldBlock, options };
    });
    const isBlocking = useCallback(() => {
        const { shouldBlock } = latest.current;

        return shouldBlock instanceof Function ? shouldBlock() : shouldBlock;
    }, []);
    const shouldBlockFn = useCallback(
        async ({ action, current, next }: Parameters<ShouldBlockFn>[0]): Promise<boolean> => {
            if (!isBlocking()) {
                return false;
            }

            const { confirm, message = UNSAVED_CHANGES_MESSAGE } = latest.current.options;

            if (!confirm) {
                return !window.confirm(message);
            }

            try {
                return !(await confirm({
                    action,
                    current: {
                        pathname: current.pathname,
                        params: routeParamStrings(current.params),
                        search: current.search,
                    },
                    next: { pathname: next.pathname, params: routeParamStrings(next.params), search: next.search },
                }));
            } catch (error) {
                console.error(error);

                return !window.confirm(message);
            }
        },
        [isBlocking]
    );
    const enableBeforeUnload = useCallback(
        () => latest.current.options.beforeUnload !== false && isBlocking(),
        [isBlocking]
    );

    useBlocker({ shouldBlockFn, enableBeforeUnload });
}
