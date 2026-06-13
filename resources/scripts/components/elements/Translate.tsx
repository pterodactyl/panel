import React from 'react';
import { Trans, TransSelectorProps, useTranslation } from 'react-i18next';

type Props = Omit<TransSelectorProps<any, any>, 't'>;

export default ({ ns, children, ...props }: Props) => {
    const { t } = useTranslation(ns);

    return (
        <Trans t={t} {...props}>
            {children}
        </Trans>
    );
};
