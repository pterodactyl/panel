import { useState } from 'react';
import Button from '@/components/elements/Button';
import SetupTOTPDialog from '@/components/dashboard/forms/SetupTOTPDialog';
import RecoveryTokensDialog from '@/components/dashboard/forms/RecoveryTokensDialog';
import DisableTOTPDialog from '@/components/dashboard/forms/DisableTOTPDialog';
import { useCurrentUser } from '@/api/account/queries';

function ConfigureTwoFactorForm() {
    const [tokens, setTokens] = useState<string[]>([]);
    const [visible, setVisible] = useState<'enable' | 'disable' | null>(null);
    const isEnabled = useCurrentUser().useTotp;

    const onTokens = (tokens: string[]) => {
        setTokens(tokens);
        setVisible(null);
    };

    return (
        <div>
            <SetupTOTPDialog
                open={visible === 'enable'}
                enabled={visible === 'enable'}
                onClose={() => setVisible(null)}
                onTokens={onTokens}
            />
            <RecoveryTokensDialog tokens={tokens} open={tokens.length > 0} onClose={() => setTokens([])} />
            <DisableTOTPDialog open={visible === 'disable'} onClose={() => setVisible(null)} />
            <p className='text-sm'>
                {isEnabled
                    ? 'Two-step verification is currently enabled on your account.'
                    : 'You do not currently have two-step verification enabled on your account. Click the button below to begin configuring it.'}
            </p>
            <div className='mt-6'>
                {isEnabled ? (
                    <Button.Danger onClick={() => setVisible('disable')}>Disable Two-Step</Button.Danger>
                ) : (
                    <Button onClick={() => setVisible('enable')}>Enable Two-Step</Button>
                )}
            </div>
        </div>
    );
}

export default ConfigureTwoFactorForm;
