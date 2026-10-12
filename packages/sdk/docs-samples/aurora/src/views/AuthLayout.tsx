import { Main, useThemeSettings, type ViewProps } from '@pterodactyl/sdk/theme';
import type { ThemeSettings } from '../theme-types';
import SchemeSwitch from '../SchemeSwitch';

/**
 * A split screen: the brand on one side, the sign-in pages on the other. It imports nothing from
 * `@pterodactyl/sdk`, so the sign-in screen renders without loading the full SDK first.
 */
export default function AuthLayout({ model, children }: ViewProps<'layout.auth'>) {
    const { tagline } = useThemeSettings<ThemeSettings>();

    return (
        <div className='grid min-h-dvh lg:grid-cols-2'>
            <section className='relative hidden flex-col justify-between overflow-hidden bg-primary p-12 text-primary-foreground lg:flex'>
                {model.brand.logoUrl ? (
                    <img src={model.brand.logoUrl} alt={model.brand.name} className='h-10 w-auto self-start' />
                ) : (
                    <p className='text-2xl font-semibold'>{model.brand.name}</p>
                )}
                <p className='max-w-sm text-4xl leading-tight font-semibold text-balance'>{tagline}</p>
            </section>
            <div className='flex flex-col'>
                <div className='flex justify-end p-4'>
                    <SchemeSwitch />
                </div>
                <Main className='flex flex-1 items-center justify-center p-6'>
                    <div className='w-full max-w-sm'>{children}</div>
                </Main>
            </div>
        </div>
    );
}
