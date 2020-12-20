import React from 'react';
import ScreenBlock from '@/components/screens/ScreenBlock';
import { useTranslation } from 'react-i18next';

interface Props {
    title?: string;
    message: string;
    onRetry?: () => void;
    onBack?: () => void;
}

export default ({ title, message, onBack, onRetry }: Props) => {
    const { t } = useTranslation('screens');

    return (
        // @ts-ignore
        <ScreenBlock
            title={title || t('something_went_wrong')}
            image={'/assets/svgs/server_error.svg'}
            message={message}
            onBack={onBack}
            onRetry={onRetry}
        />
    );
};
