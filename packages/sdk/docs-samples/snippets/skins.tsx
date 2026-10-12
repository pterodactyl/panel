import type { ReactNode } from 'react';
import { defineTheme, skin, useSkin, type PrimitiveProps } from '@pterodactyl/sdk/theme';

// #region recipe
/** A class-variance-authority recipe: variant keys are the primitive's state, `true`/`false` for booleans. */
const badge = skin('Badge', {
    root: {
        base: 'inline-flex items-center gap-1 rounded-full font-medium',
        variants: {
            size: { sm: 'px-2 text-[0.6875rem]', md: 'px-2.5 py-0.5 text-xs' },
            variant: { solid: 'text-white', soft: 'bg-transparent ring-1 ring-inset' },
        },
        compoundVariants: [
            { variant: 'solid', tone: ['success', 'info'], class: 'bg-success' },
            { variant: 'solid', tone: ['danger', 'warning'], class: 'bg-destructive' },
            { variant: 'solid', tone: ['neutral', 'accent'], class: 'bg-primary' },
            { variant: 'soft', tone: 'danger', class: 'text-destructive ring-destructive/40' },
        ],
        defaultVariants: { size: 'md' },
    },
});
// #endregion recipe

// #region component
/** A presentational primitive can be replaced by a component that takes the same props. */
function Banner({ type, title, className, children }: PrimitiveProps<'Alert'>) {
    return (
        <div role='alert' data-ui='alert' data-type={type} className={`rounded-xl border-l-4 p-4 ${className ?? ''}`}>
            {title && <p className='font-semibold'>{title}</p>}
            {children}
        </div>
    );
}
// #endregion component

// #region use-skin
/** A theme's own element styled with the active skin of a primitive, attributes included. */
function ButtonLikeLink({ href, children }: { href: string; children: ReactNode }) {
    const ui = useSkin('Button', { color: 'primary', size: 'small', secondary: true, loading: false });

    return (
        <a href={href} {...ui('root', 'no-underline')}>
            {children}
        </a>
    );
}
// #endregion use-skin

export default defineTheme({ ui: { Badge: badge, Alert: Banner } });

export { ButtonLikeLink };
