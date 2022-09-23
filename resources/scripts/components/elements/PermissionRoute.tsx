import React from 'react';
import { Route } from 'react-router-dom';
import { RouteProps } from 'react-router';
import Can from '@/components/elements/Can';
import { ServerError } from '@/components/elements/ScreenBlock';

interface Props extends Omit<RouteProps, 'path'> {
    path: string;
    permission: string | string[] | null;
}

export default ({ permission, children, ...props }: Props) => (
    <Route {...props}>
        {!permission ? (
            children
        ) : (
            <Can
                matchAny
                action={permission}
                renderOnError={
                    <ServerError title={'Access Denied'} message={'You do not have permission to access this page.'} />
                }
            >
                {children}
            </Can>
        )}
    </Route>
);
