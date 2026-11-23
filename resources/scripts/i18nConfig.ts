import type { InitOptions } from 'i18next';

export const translationOptions = {
    fallbackLng: 'en',
    ns: ['translation'],
    defaultNS: 'translation',
    keySeparator: '.',
    nsSeparator: false,
    interpolation: { escapeValue: false },
} satisfies InitOptions;
