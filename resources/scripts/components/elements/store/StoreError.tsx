import React from 'react';
import { Alert } from '@/components/elements/alert';
import PageContentBlock from '@/components/elements/PageContentBlock';

interface ErrorProps {
    message: string;
    admin: string;
}

export default ({ message, admin }: ErrorProps) => (
    <PageContentBlock>
        <Alert type={'error'}>{message}</Alert>
        <Alert type={'warning'} className={'mt-2'}>
            (Admin Message) {admin}
        </Alert>
    </PageContentBlock>
);
