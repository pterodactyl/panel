import { useCallback, useContext } from 'react';
import { useTranslation as useI18nextTranslation } from 'react-i18next';
import { ExtensionContext } from '@/extensions/context';

export interface ExtensionTranslation {
    locale: string;
    ready: boolean;
    t(key: string, values?: Record<string, string | number>): string;
}

export type Translation = ExtensionTranslation;

export const translatable = (text: string): string => text;

export function useTranslation(): Translation {
    const { t, i18n, ready } = useI18nextTranslation('translation', { useSuspense: false });
    const translate = useCallback((key: string, values?: Record<string, string | number>) => t(key, values), [t]);

    return { locale: i18n.language, ready, t: translate };
}

/** resources/lang/<locale>/<group>.php, registered with loadExtensionTranslations(). */
export function useExtensionTranslation(group = 'messages'): ExtensionTranslation {
    const mount = useContext(ExtensionContext);

    if (!mount) {
        throw new Error('Extension translations require an extension mount.');
    }

    if (!/^[a-z][a-z0-9_-]{0,63}$/.test(group)) {
        throw new Error('Invalid extension translation group.');
    }

    const namespace = `ext-${mount.extensionId}::${group}`;
    const { t, i18n, ready } = useI18nextTranslation(namespace, { useSuspense: false });

    return { locale: i18n.language, ready, t: (key, values) => t(key, { ...values, nsSeparator: false }) };
}
