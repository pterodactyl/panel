import { View, type ViewProps } from '@/sdk/theme';

export default function LoginPage({ className }: ViewProps<'page.auth.login'>) {
    return <View name='auth.loginForm' className={className} />;
}
