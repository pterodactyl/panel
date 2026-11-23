import { Alert, Button, CopyOnClick, Dialog, useTranslation } from '@/sdk';

export default function RecoveryTokensDialog({ tokens, onClose }: { tokens: readonly string[]; onClose: () => void }) {
    const { t } = useTranslation();
    const grouped: [string, string][] = [];

    for (const [index, token] of tokens.entries()) {
        if (index % 2 === 0) {
            grouped.push([token, tokens[index + 1] || '']);
        }
    }

    return (
        <Dialog
            open={tokens.length > 0}
            onClose={onClose}
            title={t('Two-Step Authentication Enabled')}
            description={t(
                'Store the codes below somewhere safe. If you lose access to your phone you can use these backup codes to sign in.'
            )}
            hideCloseIcon
            preventExternalClose
        >
            <Dialog.Icon position='container' type='success' />
            <CopyOnClick text={tokens.join('\n')} showInNotification={false}>
                <pre className='bg-background rounded-sm p-2 mt-6'>
                    {grouped.map((value) => (
                        <span key={value.join('_')} className='block'>
                            {value[0]}
                            <span className='mx-2 selection:bg-background'>&nbsp;</span>
                            {value[1]}
                            <span className='selection:bg-background'>&nbsp;</span>
                        </span>
                    ))}
                </pre>
            </CopyOnClick>
            <Alert type='danger' className='mt-3'>
                {t('These codes will not be shown again.')}
            </Alert>
            <Dialog.Footer>
                <Button.Text onClick={onClose}>{t('Done')}</Button.Text>
            </Dialog.Footer>
        </Dialog>
    );
}
