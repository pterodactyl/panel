import React, { useState } from 'react';
import i18n from '@/i18n';

interface Props {
    ns: string;
    i18nKey: string;
    replace: any;
}

export default ({ ns, i18nKey, replace }: Props) => {
    const [translation, setTranslation] = useState('');

    async function load() {
        await i18n.loadNamespaces(ns);
        setTranslation(i18n.t(i18nKey, { ns: ns, replace: replace }));
    }

    load();

    return <>{translation}</>;
};
