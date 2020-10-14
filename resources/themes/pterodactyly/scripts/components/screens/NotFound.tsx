import React from 'react';
import ScreenBlock from '@/components/screens/ScreenBlock';

interface Props {
    title?: string;
    message?: string;
    onBack?: () => void;
}

export default ({ title, message, onBack }: Props) => (
    <ScreenBlock
        title={title || '404'}
        image={'/assets/svgs/not_found.svg'}
        message={message || 'The requested resource was not found.'}
        onBack={onBack}
    />
);
