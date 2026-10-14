import { Main, useThemeSettings, type ViewProps } from '@pterodactyl/sdk/theme';
import type { ThemeSettings } from './theme-types';

/**
 * The sign-in pages beside the brand and the "tagline" setting. It imports only
 * `@pterodactyl/sdk/theme`, so the sign-in screen renders without loading the full SDK.
 */
export default function AuthLayout({ model, children }: ViewProps<'layout.auth'>) {
    // #region read
    const { tagline } = useThemeSettings<ThemeSettings>();
    // #endregion read

    return (
        <div className='grid min-h-dvh lg:grid-cols-2'>
            <section className='hidden flex-col justify-between bg-primary p-12 text-primary-foreground lg:flex'>
                {model.brand.logoUrl ? (
                    <img src={model.brand.logoUrl} alt={model.brand.name} className='h-10 w-auto self-start' />
                ) : (
                    <p className='text-2xl font-semibold'>{model.brand.name}</p>
                )}
                <p className='max-w-sm text-4xl leading-tight font-semibold text-balance'>{tagline}</p>
            </section>
            <Main className='flex items-center justify-center p-6'>
                <div className='w-full max-w-sm'>{children}</div>
            </Main>
        </div>
    );
}
