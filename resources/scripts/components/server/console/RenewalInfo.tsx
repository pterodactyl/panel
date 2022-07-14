import React, { useState } from 'react';
import useFlash from '@/plugins/useFlash';
import { useStoreState } from '@/state/hooks';
import { ServerContext } from '@/state/server';
import renewServer from '@/api/server/renewServer';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

export default () => {
    const [open, setOpen] = useState(false);
    const { clearAndAddHttpError } = useFlash();
    const [loading, setLoading] = useState(false);
    const store = useStoreState((state) => state.storefront.data!);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const renewal = ServerContext.useStoreState((state) => state.server.data!.renewal);

    const doRenewal = () => {
        setLoading(true);

        renewServer(uuid)
            .then(() => setOpen(false))
            .catch((error) => clearAndAddHttpError(error));
    };

    return (
        <>
            <Dialog.Confirm
                open={open}
                onClose={() => setOpen(false)}
                title={'Confirm server renewal'}
                onConfirmed={() => doRenewal()}
            >
                <SpinnerOverlay visible={loading} />
                You will be charged {store.renewals.cost} {store.currency} to add {store.renewals.days} days until your
                next renewal is due.
            </Dialog.Confirm>
            in {renewal} days{' '}
            <span className={'text-blue-500 text-sm cursor-pointer'} onClick={() => setOpen(true)}>
                {'('}Renew{')'}
            </span>
        </>
    );
};
