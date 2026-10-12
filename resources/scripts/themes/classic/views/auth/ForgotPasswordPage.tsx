import { View, type ViewProps } from '@/sdk/theme';

export default function ForgotPasswordPage({ className }: ViewProps<'page.auth.password'>) {
    return <View name='auth.forgotPasswordForm' className={className} />;
}
