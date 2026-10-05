import type { DialogProps } from '@/components/elements/dialog';
import { Dialog } from '@/components/elements/dialog';
import Button from '@/components/elements/Button';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { Alert } from '@/components/elements/alert';

interface RecoveryTokenDialogProps extends DialogProps {
    tokens: string[];
}

function RecoveryTokensDialog({ tokens, open, onClose }: RecoveryTokenDialogProps) {
    const grouped = [] as [string, string][];
    tokens.forEach((token, index) => {
        if (index % 2 === 0) {
            grouped.push([token, tokens[index + 1] || '']);
        }
    });

    return (
        <Dialog
            open={open}
            onClose={onClose}
            title={'Two-Step Authentication Enabled'}
            description={
                'Store the codes below somewhere safe. If you lose access to your phone you can use these backup codes to sign in.'
            }
            hideCloseIcon
            preventExternalClose
        >
            <Dialog.Icon position={'container'} type={'success'} />
            <CopyOnClick text={tokens.join('\n')} showInNotification={false}>
                <pre className={'bg-background rounded-sm p-2 mt-6'}>
                    {grouped.map((value) => (
                        <span key={value.join('_')} className={'block'}>
                            {value[0]}
                            <span className={'mx-2 selection:bg-background'}>&nbsp;</span>
                            {value[1]}
                            <span className={'selection:bg-background'}>&nbsp;</span>
                        </span>
                    ))}
                </pre>
            </CopyOnClick>
            <Alert type={'danger'} className={'mt-3'}>
                These codes will not be shown again.
            </Alert>
            <Dialog.Footer>
                <Button.Text onClick={onClose}>Done</Button.Text>
            </Dialog.Footer>
        </Dialog>
    );
}

export default RecoveryTokensDialog;
