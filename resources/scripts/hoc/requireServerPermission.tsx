import React from 'react';
import Can from '@/components/elements/Can';
import NotFound from '@/components/screens/NotFound';
import ScreenBlock from '@/components/screens/ScreenBlock';

const requireServerPermission = (Component: React.ComponentType<any>, permissions: string | string[]) => {
    return class extends React.Component<any, any> {
        render () {
            return (
                <Can
                    action={permissions}
                    renderOnError={
                        <ScreenBlock
                            image={'/assets/svgs/server_error.svg'}
                            title={'Access Denied'}
                            message={'You do not have permission to access this page.'}
                        />
                    }
                >
                    <Component/>
                </Can>
            );
        }
    };
};

export default requireServerPermission;
