import { Main, type ViewProps } from '@/sdk/theme';

export default function AuthLayout({ children }: ViewProps<'layout.auth'>) {
    return (
        <div className='pt-8 xl:pt-32'>
            <Main>{children}</Main>
        </div>
    );
}
