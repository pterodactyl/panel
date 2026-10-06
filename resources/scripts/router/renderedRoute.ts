import { useMatches } from '@tanstack/react-router';

const withoutTrailingSlash = (path: string): string => path.replace(/\/+$/, '');

/**
 * Whether `<Outlet />` is currently rendering the page at `path`.
 *
 * Use this, not the location, for anything that has to change in the same render as the
 * outlet. The location points at the destination as soon as a navigation starts, while the
 * outlet keeps the page being left on screen until the destination has loaded.
 */
export function useIsRenderedRoute(path: string): boolean {
    return useMatches({
        select: (matches) => {
            const rendered = matches.at(-1)?.pathname;

            return rendered !== undefined && withoutTrailingSlash(rendered) === withoutTrailingSlash(path);
        },
    });
}
