import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

export type TranslationValue =
    | string
    | number
    | boolean
    | null
    | readonly TranslationValue[]
    | { readonly [key: string]: TranslationValue };

export type TranslationValues = Record<string, TranslationValue>;

interface Props {
    ns: string;
    i18nKey: string;
    values?: TranslationValues;
    children?: React.ReactNode;
}

export default function Translate({ ns, children, ...props }: Props) {
    const { t } = useTranslation(ns);

    return (
        <Trans t={t} {...props}>
            {children}
        </Trans>
    );
}
