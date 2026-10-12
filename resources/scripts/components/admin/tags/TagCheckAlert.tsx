import { Alert } from '@/components/elements/alert';
import Button from '@/components/elements/Button';

interface Props {
    message: string;
    /** Labels a button that repeats the request with the tag check skipped. */
    action?: string;
    pending?: boolean;
    onAction?: () => void;
}

export default function TagCheckAlert({ message, action, pending, onAction }: Props) {
    return (
        <Alert type='warning'>
            <div className='flex flex-wrap items-center justify-between gap-3'>
                <span>{message}</span>
                {action && onAction && (
                    <Button size='xsmall' color='orange' isLoading={pending} onClick={onAction}>
                        {action}
                    </Button>
                )}
            </div>
        </Alert>
    );
}
