import React, { useState } from 'react';
import useFlash from '@/plugins/useFlash';
import { httpErrorToHuman } from '@/api/http';
import { useStoreState } from '@/state/hooks';
import { ServerContext } from '@/state/server';
import renewServer from '@/api/server/renewServer';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

export default () => {
    const [open, setOpen] = useState(false);
    const { addFlash, clearFlashes } = useFlash();
    const [loading, setLoading] = useState(false);
    const store = useStoreState((state) => state.storefront.data!);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const renewal = ServerContext.useStoreState((state) => state.server.data!.renewal);

    const doRenewal = () => {
        setLoading(true);
        clearFlashes('console:share');

        renewServer(uuid)
            .then(() => {
                setOpen(false);
                setLoading(false);

                addFlash({
                    key: 'console:share',
                    type: 'success',
                    message: 'Server has been renewed.',
                });
            })
            .catch((error) => {
                setOpen(false);
                setLoading(false);

                console.log(httpErrorToHuman(error));
                addFlash({
                    key: 'console:share',
                    type: 'error',
                    message: 'Unable to renew your server. Are you sure you have enough credits?',
                });
            });
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
