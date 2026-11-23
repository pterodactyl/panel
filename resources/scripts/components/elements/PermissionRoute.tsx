import React from 'react';
import Can from '@/components/elements/Can';
import { AccessDenied } from '@/components/elements/ScreenBlock';
import { useTranslation } from '@/sdk/localization';

interface Props {
    permission: string | string[] | null;
    children?: React.ReactNode;
}

export default function PermissionRoute({ permission, children }: Props) {
    const { t } = useTranslation();

    if (!permission) {
        return children;
    }

    return (
        <Can
            matchAny
            action={permission}
            renderOnError={<AccessDenied message={t('You do not have permission to access this page.')} />}
        >
            {children}
        </Can>
    );
}
