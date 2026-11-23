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
    ns?: string;
    i18nKey: string;
    values?: TranslationValues;
    components?: Readonly<Record<string, React.ReactElement>>;
    valueComponent?: React.ReactElement<{ children?: React.ReactNode }>;
    children?: React.ReactNode;
}

// Trans parses interpolated values as markup, so text is passed as an element to keep it literal.
function literalValues(values: TranslationValues, wrapper: Props['valueComponent']) {
    const elements: Record<string, React.ReactElement> = {};

    const visit = (value: TranslationValue): TranslationValue => {
        if (value === null || value === true || value === false || Number.isFinite(value)) {
            return value;
        }

        if (Array.isArray(value)) {
            return value.map(visit);
        }

        if (value instanceof Object) {
            return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, visit(item)]));
        }

        const name = `value-${Object.keys(elements).length}`;
        const text = String(value);

        elements[name] = wrapper
            ? React.cloneElement(wrapper, undefined, text)
            : React.createElement(React.Fragment, undefined, text);

        return `<${name}/>`;
    };

    return { values: Object.fromEntries(Object.entries(values).map(([key, value]) => [key, visit(value)])), elements };
}

export default function Translate({
    ns = 'translation',
    children,
    values,
    components,
    valueComponent,
    ...props
}: Props) {
    const { t } = useTranslation(ns);
    const literal = literalValues(values ?? {}, valueComponent);

    return (
        <Trans t={t} {...props} values={literal.values} components={{ ...components, ...literal.elements }}>
            {children}
        </Trans>
    );
}
