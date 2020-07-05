import React from 'react';
import ScreenBlock from '@/components/screens/ScreenBlock';

interface Props {
    title?: string;
    message: string;
    onRetry?: () => void;
    onBack?: () => void;
}

export default ({ title, message, onBack, onRetry }: Props) => (
    // @ts-ignore
    <ScreenBlock
        title={title || 'Something went wrong'}
        image={'/assets/svgs/server_error.svg'}
        message={message}
        onBack={onBack}
        onRetry={onRetry}
    />
);
