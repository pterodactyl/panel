import type { TransProps } from 'react-i18next';
import { Trans, useTranslation } from 'react-i18next';

type Props = Omit<TransProps<string, string>, 't'>;

function Translate({ ns, children, ...props }: Props) {
    const { t } = useTranslation(ns);

    return (
        <Trans t={t} {...props}>
            {children}
        </Trans>
    );
}

export default Translate;
