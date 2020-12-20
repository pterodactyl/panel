import React from 'react';
import ScreenBlock from '@/components/screens/ScreenBlock';
import { useTranslation } from 'react-i18next';

interface Props {
    title?: string;
    message?: string;
    onBack?: () => void;
}

export default ({ title, message, onBack }: Props) => {
    const { t } = useTranslation('screens');

    return (
        <ScreenBlock
            title={title || '404'}
            image={'/assets/svgs/not_found.svg'}
            message={message || t('resource_not_found')}
            onBack={onBack}
        />
    );
};
