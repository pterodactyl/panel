import React, { useState } from 'react';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SetupOAuthModal from "@/components/dashboard/forms/SetupOAuthModal";

export default () => {
    const user = useStoreState((state: ApplicationStore) => state.user.data!);
    const [ visible, setVisible ] = useState(false);

    return (
        <div>
            {visible &&
            <SetupOAuthModal
                appear={true}
                visible={visible}
                onDismissed={() => setVisible(false)}
            />
            }
            <p className={'text-sm'}>
                Click the button below to setup your linked OAuth accounts
            </p>
            <div className={'mt-6'}>
                <button
                    onClick={() => setVisible(true)}
                    className={'btn btn-green btn-secondary btn-sm'}
                >
                    Begin Setup
                </button>
            </div>
        </div>
    );
};
