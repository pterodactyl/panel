import { cn, Main, type ViewProps } from '@/sdk/theme';
import { AuthBrandContext } from '../auth/brand';

export default function AuthLayout({ model, className, children }: ViewProps<'layout.auth'>) {
    return (
        <AuthBrandContext value={model.brand}>
            <div className={cn('pt-8 xl:pt-32', className)}>
                <Main>{children}</Main>
            </div>
        </AuthBrandContext>
    );
}
