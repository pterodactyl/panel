import i18next from 'i18next';

export const validateShortCode = (value: string): string | undefined => {
    if (value.length < 1) {
        return i18next.t('A short code must be provided.');
    }

    if (value.length > 60) {
        return i18next.t('A short code must not exceed 60 characters.');
    }

    return undefined;
};
