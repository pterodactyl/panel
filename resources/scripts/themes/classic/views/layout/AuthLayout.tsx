import { cn, Main, type ViewProps } from '@/sdk/theme';

export default function AuthLayout({ className, children }: ViewProps<'layout.auth'>) {
    return (
        <div className={cn('pt-8 xl:pt-32', className)}>
            <Main>{children}</Main>
        </div>
    );
}
