import { View, type ViewProps } from '@/sdk/theme';

export default function ResetPasswordPage({ className }: ViewProps<'page.auth.passwordReset'>) {
    return <View name='auth.resetPasswordForm' className={className} />;
}
