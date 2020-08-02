import React from 'react';
import useFlash from '@/plugins/useFlash';
import { Actions } from 'easy-peasy';
import { ApplicationStore } from '@/state';

export interface WithFlashProps {
    flash: Actions<ApplicationStore>['flashes'];
}

function withFlash<TOwnProps> (Component: React.ComponentType<TOwnProps & WithFlashProps>): React.ComponentType<TOwnProps> {
    return (props: TOwnProps) => {
        const { addError, addFlash, clearFlashes, clearAndAddHttpError } = useFlash();

        return (
            <Component
                flash={{ addError, addFlash, clearFlashes, clearAndAddHttpError }}
                {...props}
            />
        );
    };
}

export default withFlash;
